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
     * buka sesi kasir baru dengan input modal awal laci kasir
     */
    public function openSession(
        string $workspaceId,
        string $branchId,
        string $cashierUserId,
        float $openingCash,
        ?string $notes = null
    ): PosSession {
        // cek apakah sudah ada sesi kasir aktif yang terbuka di cabang ini
        $activeSession = PosSession::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('status', 'OPEN')
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

        return PosSession::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'opened_by_user_id' => $cashierUserId,
            'opening_cash' => $openingCash,
            'status' => 'OPEN',
            'opened_at' => Carbon::now(),
            'notes' => $notes,
        ]);
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
                'pos_session_id' => ['Tidak ditemukan sesi kasir aktif yang sedang terbuka.'],
            ]);
        }

        // kalkulasi total penerimaan kas tunai selama sesi ini
        $cashSales = (float) Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('pos_session_id', $session->id)
            ->where('payment_method', 'CASH')
            ->where('payment_status', 'PAID')
            ->sum('final_amount');

        $openingCash = (float) $session->opening_cash;
        $closingCashExpected = $openingCash + $cashSales;
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
     * sinkronisasi batch transaksi penjualan offline dengan jaminan idempoten
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

                $order = Order::create([
                    'workspace_id' => $workspaceId,
                    'branch_id' => $branchId,
                    'pos_session_id' => $orderData['pos_session_id'] ?? null,
                    'pos_terminal_id' => $posTerminalId,
                    'cashier_user_id' => $orderData['cashier_user_id'] ?? null,
                    'client_order_id' => $clientOrderId,
                    'order_number' => (string) $orderData['order_number'],
                    'total_amount' => (float) $orderData['total_amount'],
                    'discount_amount' => (float) ($orderData['discount_amount'] ?? 0.00),
                    'final_amount' => (float) $orderData['final_amount'],
                    'payment_method' => (string) $orderData['payment_method'],
                    'payment_status' => (string) ($orderData['payment_status'] ?? 'PAID'),
                ]);

                if (! empty($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'] ?? null,
                            'product_name' => (string) $item['product_name'],
                            'unit_price' => (float) $item['unit_price'],
                            'quantity' => (int) $item['quantity'],
                            'subtotal' => (float) $item['subtotal'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }

                $syncedIds[] = $order->id;
            }

            return [
                'synced_count' => count($syncedIds),
                'order_ids' => $syncedIds,
            ];
        });
    }
}
