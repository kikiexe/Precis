<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosService
{
    /**
     * ambil katalog kategori dan produk aktif untuk cache offline tablet POS
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCatalog(string $workspaceId): array
    {
        return Category::withoutGlobalScopes()
            ->with(['products' => function ($q): void {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get()
            ->map(function (Category $category): array {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products' => $category->products->map(function (Product $product): array {
                        return [
                            'id' => $product->id,
                            'category_id' => $product->category_id,
                            'name' => $product->name,
                            'base_price' => (float) $product->base_price,
                            'is_active' => $product->is_active,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * buka sesi kasir baru dengan input modal awal laci kasir dan verifikasi PIN
     */
    public function openSession(
        string $workspaceId,
        string $branchId,
        string $cashierUserId,
        string $pin,
        float $openingCash,
        ?string $notes = null
    ): PosSession {
        return DB::transaction(function () use (
            $workspaceId,
            $branchId,
            $cashierUserId,
            $pin,
            $openingCash,
            $notes
        ): PosSession {
            // kunci cabang dengan pessimistic lock untuk mencegah race condition (TOCTOU)
            $branch = \App\Models\Branch::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $branch) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Cabang tidak ditemukan pada workspace ini.'],
                ]);
            }

            // cek apakah sudah ada sesi kasir aktif yang terbuka di cabang ini
            $activeSession = PosSession::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('status', 'OPEN')
                ->lockForUpdate()
                ->first();

            if ($activeSession) {
                throw ValidationException::withMessages([
                    'status' => ['Sesi kasir aktif masih terbuka pada cabang ini. Tutup sesi sebelumnya terlebih dahulu.'],
                ]);
            }

            /** @var WorkspaceMember|null $member */
            $member = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $cashierUserId)
                ->where('is_active', true)
                ->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'cashier_user_id' => ['Kasir bukan anggota aktif di workspace ini.'],
                ]);
            }

            // verifikasi PIN kasir
            if (empty($member->pin) || ! Hash::check($pin, (string) $member->pin)) {
                throw ValidationException::withMessages([
                    'pin' => ['PIN kasir tidak valid.'],
                ]);
            }

            // jika member terikat ke cabang tertentu, pastikan sesuai dengan cabang terminal
            if ($member->branch_id && $member->branch_id !== $branchId) {
                throw ValidationException::withMessages([
                    'cashier_user_id' => ['Kasir tidak ditugaskan pada cabang terminal POS ini.'],
                ]);
            }

            return PosSession::create([
                'workspace_id' => $workspaceId,
                'branch_id' => $branchId,
                'opened_by_user_id' => $cashierUserId,
                'opening_cash' => $openingCash,
                'status' => 'OPEN',
                'opened_at' => Carbon::now(),
                'notes' => $notes,
            ]);
        });
    }

    /**
     * tutup sesi kasir dan lakukan rekonsiliasi kas (hitung selisih fisik vs sistem)
     */
    public function closeSession(
        string $workspaceId,
        string $branchId,
        ?string $posSessionId,
        float $closingCashActual,
        ?string $closedByUserId = null,
        ?string $notes = null
    ): PosSession {
        /** @var PosSession|null $session */
        if ($posSessionId) {
            $session = PosSession::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('id', $posSessionId)
                ->where('status', 'OPEN')
                ->first();
        } else {
            $session = PosSession::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('status', 'OPEN')
                ->latest('opened_at')
                ->first();
        }

        if (! $session) {
            throw ValidationException::withMessages([
                'pos_session_id' => ['Tidak ditemukan sesi kasir aktif yang sedang terbuka pada cabang ini.'],
            ]);
        }

        // kalkulasi total penerimaan kas tunai selama sesi ini
        $cashSales = (float) Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('pos_session_id', $session->id)
            ->where('payment_method', 'CASH')
            ->where('payment_status', 'PAID')
            ->sum('final_amount');

        // kalkulasi pengeluaran belanja kas laci kasir (CASH_DRAWER) selama sesi ini
        $cashPurchases = (float) \App\Models\OutletPurchase::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('pos_session_id', $session->id)
            ->where('funding_source', 'CASH_DRAWER')
            ->sum('total_price');

        // kalkulasi refund kas tunai selama sesi ini jika ada
        $cashRefunds = (float) Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('pos_session_id', $session->id)
            ->where('payment_method', 'CASH')
            ->where('payment_status', 'REFUNDED')
            ->sum('refund_amount');

        $openingCash = (float) $session->opening_cash;
        $closingCashExpected = $openingCash + $cashSales - $cashPurchases - $cashRefunds;
        $discrepancyAmount = $closingCashActual - $closingCashExpected;

        $session->update([
            'closed_by_user_id' => $closedByUserId ?? $session->opened_by_user_id,
            'closing_cash_actual' => $closingCashActual,
            'closing_cash_expected' => $closingCashExpected,
            'discrepancy_amount' => $discrepancyAmount,
            'status' => 'CLOSED',
            'closed_at' => Carbon::now(),
            'notes' => $notes ?? $session->notes,
        ]);

        return $session;
    }

    /**
     * sinkronisasi batch transaksi penjualan offline dengan jaminan idempoten dan verifikasi harga server-side
     *
     * @param  array<int, array<string, mixed>>  $ordersPayload
     * @return array{synced_count: int, order_ids: array<int, string>}
     */
    public function syncOrdersBatch(
        string $workspaceId,
        string $branchId,
        ?string $posTerminalId,
        array $ordersPayload
    ): array {
        return DB::transaction(function () use ($workspaceId, $branchId, $posTerminalId, $ordersPayload): array {
            $syncedIds = [];

            // kumpulkan seluruh product_id dari payload untuk prefetch harga dari database
            $productIds = [];
            foreach ($ordersPayload as $orderData) {
                if (! empty($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        if (! empty($item['product_id'])) {
                            $productIds[] = (string) $item['product_id'];
                        }
                    }
                }
            }

            /** @var \Illuminate\Support\Collection<string, Product> $products */
            $products = Product::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereIn('id', array_unique($productIds))
                ->get()
                ->keyBy('id');

            foreach ($ordersPayload as $orderData) {
                $clientOrderId = (string) $orderData['client_order_id'];

                // validasi idempoten: jika transaksi dengan client_order_id sudah ada, lewati pembuatan ulang
                /** @var Order|null $existingOrder */
                $existingOrder = Order::withoutGlobalScopes()
                    ->where('client_order_id', $clientOrderId)
                    ->first();

                if ($existingOrder) {
                    $syncedIds[] = $existingOrder->id;
                    continue;
                }

                // kalkulasi ulang subtotal dan total harga di sisi server
                $calculatedTotalAmount = 0.0;
                $processedItems = [];

                if (! empty($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        $productId = isset($item['product_id']) ? (string) $item['product_id'] : null;
                        $quantity = max(1, (int) ($item['quantity'] ?? 1));
                        /** @var Product|null $product */
                        $product = $productId ? $products->get($productId) : null;

                        // gunakan harga resmi database jika produk terdaftar, fallback ke unit_price client
                        $unitPrice = $product ? (float) $product->base_price : (float) ($item['unit_price'] ?? 0.0);
                        $productName = $product ? $product->name : (string) ($item['product_name'] ?? 'Item');
                        $subtotal = $unitPrice * $quantity;
                        $calculatedTotalAmount += $subtotal;

                        $processedItems[] = [
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'unit_price' => $unitPrice,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'notes' => $item['notes'] ?? null,
                        ];
                    }
                }

                $totalAmount = count($processedItems) > 0 ? $calculatedTotalAmount : (float) $orderData['total_amount'];
                $discountAmount = max(0.0, min($totalAmount, (float) ($orderData['discount_amount'] ?? 0.00)));
                $finalAmount = max(0.0, $totalAmount - $discountAmount);

                // validasi kasir di dalam workspace
                $cashierUserId = isset($orderData['cashier_user_id']) ? (string) $orderData['cashier_user_id'] : null;
                if ($cashierUserId) {
                    $cashierExists = WorkspaceMember::withoutGlobalScopes()
                        ->where('workspace_id', $workspaceId)
                        ->where('user_id', $cashierUserId)
                        ->where('is_active', true)
                        ->exists();

                    if (! $cashierExists) {
                        $cashierUserId = null;
                    }
                }

                // validasi sesi kasir agar tidak terjadi keracunan rekonsiliasi kas (cash reconciliation poisoning)
                $rawPosSessionId = isset($orderData['pos_session_id']) ? (string) $orderData['pos_session_id'] : null;
                $posSessionId = null;
                if ($rawPosSessionId) {
                    $sessionExists = PosSession::withoutGlobalScopes()
                        ->where('workspace_id', $workspaceId)
                        ->where('branch_id', $branchId)
                        ->where('id', $rawPosSessionId)
                        ->exists();

                    if ($sessionExists) {
                        $posSessionId = $rawPosSessionId;
                    }
                }

                $order = Order::create([
                    'workspace_id' => $workspaceId,
                    'branch_id' => $branchId,
                    'pos_session_id' => $posSessionId,
                    'pos_terminal_id' => $posTerminalId,
                    'cashier_user_id' => $cashierUserId,
                    'client_order_id' => $clientOrderId,
                    'order_number' => (string) $orderData['order_number'],
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'payment_method' => (string) $orderData['payment_method'],
                    'payment_status' => (string) ($orderData['payment_status'] ?? 'PAID'),
                ]);

                foreach ($processedItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                        'notes' => $item['notes'],
                    ]);
                }

                $syncedIds[] = $order->id;
            }

            return [
                'synced_count' => count($syncedIds),
                'order_ids' => $syncedIds,
            ];
        });
    }

    /**
     * proses void pembatalan transaksi pesanan
     */
    public function voidOrder(
        string $workspaceId,
        string $branchId,
        string $orderIdOrClientId,
        string $reason,
        ?string $pin = null,
        ?string $approvedByUserId = null
    ): Order {
        return DB::transaction(function () use ($workspaceId, $branchId, $orderIdOrClientId, $reason, $pin, $approvedByUserId): Order {
            $order = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where(function ($q) use ($orderIdOrClientId) {
                    $q->where('id', $orderIdOrClientId)
                        ->orWhere('client_order_id', $orderIdOrClientId);
                })
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Pesanan tidak ditemukan pada cabang ini.'],
                ]);
            }

            if ($order->payment_status === 'VOID') {
                throw ValidationException::withMessages([
                    'order_id' => ['Pesanan ini sudah dibatalkan (VOID) sebelumnya.'],
                ]);
            }

            if ($approvedByUserId && $pin) {
                $member = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('user_id', $approvedByUserId)
                    ->first();

                if ($member && ! empty($member->pin) && ! \Illuminate\Support\Facades\Hash::check($pin, $member->pin)) {
                    throw ValidationException::withMessages([
                        'pin' => ['PIN Otorisasi supervisor/manajer tidak valid.'],
                    ]);
                }
            }

            $order->update([
                'payment_status' => 'VOID',
                'void_reason' => $reason,
                'voided_at' => Carbon::now(),
                'voided_by_user_id' => $approvedByUserId,
            ]);

            return $order;
        });
    }

    /**
     * proses refund pengembalian dana pesanan
     */
    public function refundOrder(
        string $workspaceId,
        string $branchId,
        string $orderIdOrClientId,
        string $reason,
        ?float $refundAmount = null,
        ?string $pin = null,
        ?string $approvedByUserId = null
    ): Order {
        return DB::transaction(function () use ($workspaceId, $branchId, $orderIdOrClientId, $reason, $refundAmount, $pin, $approvedByUserId): Order {
            $order = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where(function ($q) use ($orderIdOrClientId) {
                    $q->where('id', $orderIdOrClientId)
                        ->orWhere('client_order_id', $orderIdOrClientId);
                })
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Pesanan tidak ditemukan pada cabang ini.'],
                ]);
            }

            if ($order->payment_status === 'REFUNDED') {
                throw ValidationException::withMessages([
                    'order_id' => ['Pesanan ini sudah direfund sebelumnya.'],
                ]);
            }

            $finalRefund = $refundAmount ?? (float) $order->final_amount;

            if ($approvedByUserId && $pin) {
                $member = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('user_id', $approvedByUserId)
                    ->first();

                if ($member && ! empty($member->pin) && ! \Illuminate\Support\Facades\Hash::check($pin, $member->pin)) {
                    throw ValidationException::withMessages([
                        'pin' => ['PIN Otorisasi supervisor/manajer tidak valid.'],
                    ]);
                }
            }

            $order->update([
                'payment_status' => 'REFUNDED',
                'refund_amount' => $finalRefund,
                'refund_reason' => $reason,
                'refunded_at' => Carbon::now(),
                'refunded_by_user_id' => $approvedByUserId,
            ]);

            return $order;
        });
    }

    /**
     * ambil riwayat pengeluaran belanja operasional outlet
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OutletPurchase>
     */
    public function getOutletPurchases(
        string $workspaceId,
        string $branchId,
        ?string $posSessionId = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = \App\Models\OutletPurchase::withoutGlobalScopes()
            ->with(['recordedByUser:id,name'])
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId);

        if ($posSessionId) {
            $query->where('pos_session_id', $posSessionId);
        }

        return $query->latest('created_at')->get();
    }

    /**
     * catat belanja operasional outlet (petty cash)
     *
     * @param  array<string, mixed>  $data
     */
    public function createOutletPurchase(
        string $workspaceId,
        string $branchId,
        ?string $posSessionId,
        string $recordedByUserId,
        array $data
    ): \App\Models\OutletPurchase {
        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $totalPrice = isset($data['total_price'])
            ? (float) $data['total_price']
            : round($quantity * $unitPrice, 2);

        $purchase = \App\Models\OutletPurchase::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'pos_session_id' => $posSessionId,
            'item_name' => (string) $data['item_name'],
            'unit' => (string) ($data['unit'] ?? 'Pcs'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'category' => (string) $data['category'],
            'funding_source' => (string) $data['funding_source'],
            'receipt_photo_url' => $data['receipt_photo_url'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by_user_id' => $recordedByUserId,
        ]);

        $purchase->load(['recordedByUser:id,name']);

        return $purchase;
    }
}
