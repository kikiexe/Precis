<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OutletPurchase;
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
                $q->where('is_active', true)->with('addonCategories')->orderBy('name');
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
                            'addon_category_ids' => $product->addonCategories->pluck('id')->toArray(),
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * ambil daftar kategori add-on / modifier dan opsi item aktif untuk sinkronisasi POS offline
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAddons(string $workspaceId): array
    {
        return AddonCategory::withoutGlobalScopes()
            ->with([
                'addons' => function ($q): void {
                    $q->where('is_active', true)->orderBy('name');
                },
                'products',
            ])
            ->where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get()
            ->map(function (AddonCategory $cat): array {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'selection_type' => $cat->selection_type,
                    'is_required' => (bool) $cat->is_required,
                    'min_selection' => (int) $cat->min_selection,
                    'max_selection' => (int) $cat->max_selection,
                    'product_ids' => $cat->products->pluck('id')->toArray(),
                    'addons' => $cat->addons->map(function (Addon $addon): array {
                        return [
                            'id' => $addon->id,
                            'addon_category_id' => $addon->addon_category_id,
                            'name' => $addon->name,
                            'price' => (float) $addon->price,
                            'is_active' => (bool) $addon->is_active,
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

        // kalkulasi total pengeluaran belanja kas laci (petty cash) selama sesi ini
        $cashPurchases = (float) OutletPurchase::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('pos_session_id', $session->id)
            ->where('funding_source', 'CASH_DRAWER')
            ->sum('total_price');

        // kalkulasi total pengembalian kas tunai selama sesi ini
        $cashRefunds = (float) Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('refunded_in_session_id', $session->id)
            ->where('refund_method', 'CASH_DRAWER')
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
     * batalkan pesanan pada sesi kasir yang sedang aktif (void) dengan otorisasi PIN approver
     */
    public function voidOrder(
        string $workspaceId,
        string $branchId,
        string $orderId,
        string $approverUserId,
        string $pin,
        string $reason
    ): Order {
        return DB::transaction(function () use (
            $workspaceId,
            $branchId,
            $orderId,
            $approverUserId,
            $pin,
            $reason
        ): Order {
            $order = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Transaksi pesanan tidak ditemukan pada cabang ini.'],
                ]);
            }

            if ($order->payment_status !== 'PAID') {
                throw ValidationException::withMessages([
                    'status' => ["Pesanan dengan status {$order->payment_status} tidak dapat dibatalkan (void)."],
                ]);
            }

            // void hanya dapat dilakukan pada sesi kasir aktif yang sama
            $activeSession = PosSession::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('status', 'OPEN')
                ->first();

            if (! $activeSession || $order->pos_session_id !== $activeSession->id) {
                throw ValidationException::withMessages([
                    'status' => ['Pembatalan (void) hanya dapat dilakukan pada transaksi di sesi kasir yang sedang aktif. Gunakan fitur refund untuk transaksi lintas sesi.'],
                ]);
            }

            /** @var WorkspaceMember|null $approverMember */
            $approverMember = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $approverUserId)
                ->where('is_active', true)
                ->first();

            if (! $approverMember) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun penyetujui (approver) bukan anggota aktif di workspace ini.'],
                ]);
            }

            if ($approverMember->branch_id && $approverMember->branch_id !== $branchId) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun approver tidak ditugaskan pada cabang ini.'],
                ]);
            }

            $hasPermission = in_array($approverMember->role, ['OWNER', 'ADMIN', 'MANAGER'], true)
                || $approverMember->hasPermission('pos.void_order');

            if (! $hasPermission) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun approver tidak memiliki wewenang untuk otorisasi void transaksi.'],
                ]);
            }

            if (empty($approverMember->pin) || ! Hash::check($pin, (string) $approverMember->pin)) {
                throw ValidationException::withMessages([
                    'pin' => ['PIN otorisasi approver tidak valid.'],
                ]);
            }

            $order->update([
                'payment_status' => 'VOIDED',
                'void_reason' => $reason,
                'voided_by_user_id' => $approverUserId,
                'voided_at' => Carbon::now(),
            ]);

            return $order;
        });
    }

    /**
     * proses pengembalian dana pesanan (refund) sebagian atau penuh dengan otorisasi PIN approver
     */
    public function refundOrder(
        string $workspaceId,
        string $branchId,
        string $orderId,
        string $approverUserId,
        string $pin,
        string $reason,
        ?float $refundAmount,
        string $refundMethod
    ): Order {
        return DB::transaction(function () use (
            $workspaceId,
            $branchId,
            $orderId,
            $approverUserId,
            $pin,
            $reason,
            $refundAmount,
            $refundMethod
        ): Order {
            $order = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Transaksi pesanan tidak ditemukan pada cabang ini.'],
                ]);
            }

            if (! in_array($order->payment_status, ['PAID', 'PARTIALLY_REFUNDED'], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Pesanan dengan status {$order->payment_status} tidak dapat diproses refund."],
                ]);
            }

            $currentRefunded = (float) $order->refund_amount;
            $finalAmount = (float) $order->final_amount;
            $remainingRefundable = max(0.0, $finalAmount - $currentRefunded);

            if ($remainingRefundable <= 0) {
                throw ValidationException::withMessages([
                    'refund_amount' => ['Seluruh nilai pesanan ini telah selesai di-refund sebelumnya.'],
                ]);
            }

            $amountToRefund = $refundAmount ?? $remainingRefundable;

            if ($amountToRefund <= 0 || $amountToRefund > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'refund_amount' => ['Nominal refund tidak boleh melebihi sisa dana yang dapat dikembalikan (Rp ' . number_format($remainingRefundable, 0, ',', '.') . ').'],
                ]);
            }

            /** @var WorkspaceMember|null $approverMember */
            $approverMember = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $approverUserId)
                ->where('is_active', true)
                ->first();

            if (! $approverMember) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun penyetujui (approver) bukan anggota aktif di workspace ini.'],
                ]);
            }

            if ($approverMember->branch_id && $approverMember->branch_id !== $branchId) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun approver tidak ditugaskan pada cabang ini.'],
                ]);
            }

            $hasPermission = in_array($approverMember->role, ['OWNER', 'ADMIN', 'MANAGER'], true)
                || $approverMember->hasPermission('pos.refund_order')
                || $approverMember->hasPermission('pos.void_order');

            if (! $hasPermission) {
                throw ValidationException::withMessages([
                    'approver_user_id' => ['Akun approver tidak memiliki wewenang untuk otorisasi refund transaksi.'],
                ]);
            }

            if (empty($approverMember->pin) || ! Hash::check($pin, (string) $approverMember->pin)) {
                throw ValidationException::withMessages([
                    'pin' => ['PIN otorisasi approver tidak valid.'],
                ]);
            }

            $refundedInSessionId = null;
            if ($refundMethod === 'CASH_DRAWER') {
                $activeSession = PosSession::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)
                    ->where('branch_id', $branchId)
                    ->where('status', 'OPEN')
                    ->first();

                if (! $activeSession) {
                    throw ValidationException::withMessages([
                        'refund_method' => ['Pengembalian dana via kas laci (CASH_DRAWER) memerlukan sesi kasir yang sedang aktif terbuka.'],
                    ]);
                }

                $refundedInSessionId = $activeSession->id;
            }

            $newTotalRefunded = $currentRefunded + $amountToRefund;
            $newPaymentStatus = ($newTotalRefunded >= $finalAmount) ? 'REFUNDED' : 'PARTIALLY_REFUNDED';

            $order->update([
                'payment_status' => $newPaymentStatus,
                'refund_amount' => $newTotalRefunded,
                'refund_reason' => $reason,
                'refund_method' => $refundMethod,
                'refunded_in_session_id' => $refundedInSessionId,
                'refunded_by_user_id' => $approverUserId,
                'refunded_at' => Carbon::now(),
            ]);

            return $order;
        });
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

            // kumpulkan seluruh product_id dan addon_id dari payload untuk prefetch harga resmi dari database
            $productIds = [];
            $addonIds = [];
            foreach ($ordersPayload as $orderData) {
                if (! empty($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        if (! empty($item['product_id'])) {
                            $productIds[] = (string) $item['product_id'];
                        }
                        if (! empty($item['modifiers']) && is_array($item['modifiers'])) {
                            foreach ($item['modifiers'] as $mod) {
                                $aid = isset($mod['addon_id']) ? (string) $mod['addon_id'] : (isset($mod['id']) ? (string) $mod['id'] : null);
                                if ($aid) {
                                    $addonIds[] = $aid;
                                }
                            }
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

            /** @var \Illuminate\Support\Collection<string, Addon> $addons */
            $addons = Addon::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereIn('id', array_unique($addonIds))
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

                // kalkulasi ulang subtotal dan total harga di sisi server (anti-tampering)
                $calculatedTotalAmount = 0.0;
                $processedItems = [];

                if (! empty($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        $productId = isset($item['product_id']) ? (string) $item['product_id'] : null;
                        $quantity = max(1, (int) ($item['quantity'] ?? 1));
                        /** @var Product|null $product */
                        $product = $productId ? $products->get($productId) : null;
                        $baseProductPrice = $product ? (float) $product->base_price : (float) ($item['unit_price'] ?? 0.0);

                        $modifierTotal = 0.0;
                        $verifiedModifiers = [];
                        if (! empty($item['modifiers']) && is_array($item['modifiers'])) {
                            foreach ($item['modifiers'] as $mod) {
                                $aid = isset($mod['addon_id']) ? (string) $mod['addon_id'] : (isset($mod['id']) ? (string) $mod['id'] : null);
                                /** @var Addon|null $addon */
                                $addon = $aid ? $addons->get($aid) : null;
                                $addonPrice = $addon ? (float) $addon->price : (float) ($mod['price'] ?? $mod['unit_price'] ?? 0.0);
                                $addonName = $addon ? $addon->name : (string) ($mod['name'] ?? $mod['addon_name'] ?? 'Addon');
                                $modifierTotal += $addonPrice;

                                $verifiedModifiers[] = [
                                    'addon_id' => $aid,
                                    'addon_category_id' => $addon?->addon_category_id,
                                    'name' => $addonName,
                                    'addon_name' => $addonName,
                                    'price' => $addonPrice,
                                    'unit_price' => $addonPrice,
                                ];
                            }
                        }

                        // harga satuan = harga dasar produk + total harga add-on resmi server
                        $unitPrice = $baseProductPrice + $modifierTotal;
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
                            'modifiers' => count($verifiedModifiers) > 0 ? $verifiedModifiers : null,
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
                        'modifiers' => $item['modifiers'],
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
     * catat belanja operasional outlet / petty cash kasir
     *
     * @param  array<string, mixed>  $data
     */
    public function createOutletPurchase(
        string $workspaceId,
        string $branchId,
        ?string $posSessionId,
        string $recordedByUserId,
        array $data
    ): OutletPurchase {
        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $totalPrice = (float) ($data['total_price'] ?? ($quantity * $unitPrice));

        return OutletPurchase::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'pos_session_id' => $posSessionId,
            'item_name' => (string) $data['item_name'],
            'unit' => (string) ($data['unit'] ?? 'Pcs'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'category' => (string) ($data['category'] ?? 'OPERASIONAL_TOKO'),
            'funding_source' => (string) ($data['funding_source'] ?? 'CASH_DRAWER'),
            'receipt_photo_url' => $data['receipt_photo_url'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by_user_id' => $recordedByUserId,
        ]);
    }

    /**
     * ambil daftar riwayat belanja operasional outlet
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, OutletPurchase>
     */
    public function getOutletPurchases(
        string $workspaceId,
        ?string $branchId = null,
        ?string $posSessionId = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = OutletPurchase::withoutGlobalScopes()
            ->with(['recordedByUser:id,name', 'branch:id,name', 'session:id,opened_at,closed_at'])
            ->where('workspace_id', $workspaceId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($posSessionId) {
            $query->where('pos_session_id', $posSessionId);
        }

        return $query->latest('created_at')->limit(100)->get();
    }
}
