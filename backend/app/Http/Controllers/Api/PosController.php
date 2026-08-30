<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pos\ClosePosSessionRequest;
use App\Http\Requests\Pos\OpenPosSessionRequest;
use App\Http\Requests\Pos\SyncOrderBatchRequest;
use App\Models\PosTerminal;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosController
{
    public function __construct(
        private readonly PosService $posService,
    ) {
    }

    /**
     * ambil katalog menu kategori dan produk aktif untuk cache offline tablet POS
     */
    public function products(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $catalog = $this->posService->getCatalog($workspaceId);

        return new JsonResponse([
            'message' => 'Katalog produk POS berhasil dimuat.',
            'data' => $catalog,
        ], Response::HTTP_OK);
    }

    /**
     * buka sesi kasir baru dengan input modal awal laci kasir
     */
    public function openSession(OpenPosSessionRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $session = $this->posService->openSession(
            workspaceId: $workspaceId,
            branchId: $branchId,
            cashierUserId: (string) $request->validated('cashier_user_id'),
            pin: (string) $request->validated('pin'),
            openingCash: (float) $request->validated('opening_cash'),
            notes: $request->validated('notes'),
        );

        return new JsonResponse([
            'message' => 'Sesi kasir berhasil dibuka.',
            'data' => [
                'id' => $session->id,
                'branch_id' => $session->branch_id,
                'opened_by_user_id' => $session->opened_by_user_id,
                'opening_cash' => (float) $session->opening_cash,
                'status' => $session->status,
                'opened_at' => $session->opened_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * tutup sesi kasir dan hitung rekonsiliasi kas fisik vs sistem
     */
    public function closeSession(ClosePosSessionRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $session = $this->posService->closeSession(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posSessionId: $request->validated('pos_session_id'),
            closingCashActual: (float) $request->validated('closing_cash_actual'),
            closedByUserId: $request->validated('closed_by_user_id'),
            notes: $request->validated('notes'),
        );

        return new JsonResponse([
            'message' => 'Sesi kasir berhasil ditutup dan direkonsiliasi.',
            'data' => [
                'id' => $session->id,
                'opening_cash' => (float) $session->opening_cash,
                'closing_cash_expected' => (float) $session->closing_cash_expected,
                'closing_cash_actual' => (float) $session->closing_cash_actual,
                'discrepancy_amount' => (float) $session->discrepancy_amount,
                'status' => $session->status,
                'closed_at' => $session->closed_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * sinkronisasi batch transaksi pesanan offline secara idempoten
     */
    public function syncBatch(SyncOrderBatchRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');
        /** @var PosTerminal|null $terminal */
        $terminal = $request->attributes->get('current_pos_terminal');

        $result = $this->posService->syncOrdersBatch(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posTerminalId: $terminal?->id,
            ordersPayload: (array) $request->validated('orders'),
        );

        return new JsonResponse([
            'message' => 'Sinkronisasi transaksi pesanan POS berhasil diproses.',
            'data' => $result,
        ], Response::HTTP_OK);
    }

    /**
     * ambil riwayat transaksi pesanan terbaru cabang ini untuk sinkronisasi POS offline
     */
    public function orders(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $orders = \App\Models\Order::withoutGlobalScopes()
            ->with(['items'])
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (\App\Models\Order $o): array {
                return [
                    'client_order_id' => $o->client_order_id ?? (string) $o->id,
                    'order_number' => $o->order_number,
                    'workspace_id' => $o->workspace_id,
                    'branch_id' => $o->branch_id,
                    'pos_session_id' => $o->pos_session_id,
                    'cashier_user_id' => $o->cashier_user_id,
                    'cashier_name' => $o->cashier_name ?? 'Kasir',
                    'order_type' => $o->order_type,
                    'total_amount' => (float) $o->total_amount,
                    'discount_amount' => (float) $o->discount_amount,
                    'final_amount' => (float) $o->final_amount,
                    'payment_method' => $o->payment_method,
                    'cash_tendered' => $o->cash_tendered ? (float) $o->cash_tendered : null,
                    'change_amount' => $o->change_amount ? (float) $o->change_amount : null,
                    'items' => $o->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'product_name' => $i->product_name,
                        'quantity' => (int) $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                        'subtotal' => (float) $i->subtotal,
                        'notes' => $i->notes,
                    ])->toArray(),
                    'created_at' => $o->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    'sync_status' => 'SYNCED',
                ];
            });

        return new JsonResponse([
            'message' => 'Riwayat transaksi pesanan POS berhasil dimuat.',
            'data' => $orders,
        ], Response::HTTP_OK);
    }

    /**
     * ambil daftar kategori addon dan modifier aktif untuk cache POS offline
     */
    public function addons(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $categories = \App\Models\AddonCategory::withoutGlobalScopes()
            ->with(['addons' => fn ($q) => $q->where('is_active', true)])
            ->where('workspace_id', $workspaceId)
            ->get();

        return new JsonResponse([
            'message' => 'Daftar add-on dan modifier POS berhasil dimuat.',
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    /**
     * proses pembatalan / void transaksi pesanan di meja kasir
     */
    public function voidOrder(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'],
            'approved_by_user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $order = $this->posService->voidOrder(
            workspaceId: $workspaceId,
            branchId: $branchId,
            orderIdOrClientId: $id,
            reason: $validated['reason'],
            pin: $validated['pin'] ?? null,
            approvedByUserId: $validated['approved_by_user_id'] ?? null
        );

        return new JsonResponse([
            'message' => 'Transaksi pesanan berhasil dibatalkan (void).',
            'data' => $order,
        ], Response::HTTP_OK);
    }

    /**
     * proses refund pengembalian dana transaksi pesanan
     */
    public function refundOrder(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'],
            'approved_by_user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $order = $this->posService->refundOrder(
            workspaceId: $workspaceId,
            branchId: $branchId,
            orderIdOrClientId: $id,
            reason: $validated['reason'],
            refundAmount: isset($validated['refund_amount']) ? (float) $validated['refund_amount'] : null,
            pin: $validated['pin'] ?? null,
            approvedByUserId: $validated['approved_by_user_id'] ?? null
        );

        return new JsonResponse([
            'message' => 'Pengembalian dana (refund) transaksi berhasil diproses.',
            'data' => $order,
        ], Response::HTTP_OK);
    }

    /**
     * ambil riwayat pengeluaran belanja operasional outlet (petty cash)
     */
    public function purchases(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');
        $sessionId = $request->query('pos_session_id');

        $purchases = $this->posService->getOutletPurchases(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posSessionId: $sessionId ? (string) $sessionId : null
        );

        return new JsonResponse([
            'message' => 'Daftar pengeluaran belanja outlet berhasil dimuat.',
            'data' => $purchases,
        ], Response::HTTP_OK);
    }

    /**
     * catat pengeluaran belanja kas kecil (petty cash) dari kasir POS
     */
    public function storePurchase(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'in:BAHAN_BAKU_DARURAT,OPERASIONAL_TOKO,KEBERSIHAN,UTILITAS,LAINNYA'],
            'funding_source' => ['required', 'string', 'in:CASH_DRAWER,EXTERNAL_REIMBURSE'],
            'pos_session_id' => ['nullable', 'uuid'],
            'cashier_user_id' => ['required', 'uuid', 'exists:users,id'],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'],
            'receipt_photo_url' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cashierUser = \App\Models\User::findOrFail($validated['cashier_user_id']);

        if (! empty($validated['pin'])) {
            $member = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', $cashierUser->id)
                ->first();

            if ($member && ! empty($member->pin) && ! \Illuminate\Support\Facades\Hash::check($validated['pin'], $member->pin)) {
                return new JsonResponse([
                    'message' => 'PIN Kasir tidak valid.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $purchase = $this->posService->createOutletPurchase(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posSessionId: $validated['pos_session_id'] ?? null,
            recordedByUserId: $cashierUser->id,
            data: $validated
        );

        return new JsonResponse([
            'message' => 'Pengeluaran belanja outlet berhasil dicatat.',
            'data' => $purchase,
        ], Response::HTTP_CREATED);
    }

    /**
     * ambil riwayat stock waste cabang ini untuk sinkronisasi POS offline
     */
    public function wastes(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $wastes = \App\Models\StockWaste::withoutGlobalScopes()
            ->with(['product:id,name', 'recordedByUser:id,name'])
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return new JsonResponse([
            'message' => 'Riwayat stock waste berhasil dimuat.',
            'data' => $wastes,
        ], Response::HTTP_OK);
    }

    /**
     * rekam pengeluaran stock waste langsung dari tablet kasir POS
     */
    public function storeWaste(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $validated = $request->validate([
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'total_loss_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'in:EXPIRED,SPOILED,ACCIDENT_SPILL,BARISTA_MISTAKE,QC_REJECT,OTHER'],
            'photo_url' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cashier_user_id' => ['required', 'uuid', 'exists:users,id'],
            'pin' => ['nullable', 'string', 'min:4', 'max:6'],
        ]);

        $cashierUser = \App\Models\User::findOrFail($validated['cashier_user_id']);
        if (! empty($validated['pin'])) {
            $member = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', $cashierUser->id)
                ->first();

            if ($member && ! empty($member->pin) && ! \Illuminate\Support\Facades\Hash::check($validated['pin'], $member->pin)) {
                return new JsonResponse([
                    'message' => 'PIN Kasir tidak valid.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $quantity = (float) $validated['quantity'];
        $costPerUnit = (float) $validated['cost_per_unit'];
        $totalLoss = isset($validated['total_loss_cost'])
            ? (float) $validated['total_loss_cost']
            : round($quantity * $costPerUnit, 2);

        $waste = \App\Models\StockWaste::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'] ?? null,
            'item_name' => $validated['item_name'],
            'quantity' => $quantity,
            'unit' => $validated['unit'] ?? 'Pcs',
            'cost_per_unit' => $costPerUnit,
            'total_loss_cost' => $totalLoss,
            'reason' => $validated['reason'],
            'photo_url' => $validated['photo_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by_user_id' => $cashierUser->id,
        ]);

        $waste->load(['product:id,name', 'recordedByUser:id,name']);

        return new JsonResponse([
            'message' => 'Pencatatan stock waste POS berhasil disimpan.',
            'data' => $waste,
        ], Response::HTTP_CREATED);
    }
}
