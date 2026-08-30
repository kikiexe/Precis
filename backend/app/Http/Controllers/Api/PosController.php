<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pos\ClosePosSessionRequest;
use App\Http\Requests\Pos\OpenPosSessionRequest;
use App\Http\Requests\Pos\RefundOrderRequest;
use App\Http\Requests\Pos\SyncOrderBatchRequest;
use App\Http\Requests\Pos\VoidOrderRequest;
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
     * ambil master add-on / modifier dan opsi item aktif untuk sinkronisasi POS offline
     */
    public function addons(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $addons = $this->posService->getAddons($workspaceId);

        return new JsonResponse([
            'message' => 'Data modifier add-on POS berhasil dimuat.',
            'data' => $addons,
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
     * batalkan pesanan pada sesi kasir aktif (void) dengan otorisasi PIN approver
     */
    public function voidOrder(VoidOrderRequest $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $order = $this->posService->voidOrder(
            workspaceId: $workspaceId,
            branchId: $branchId,
            orderId: $id,
            approverUserId: (string) $request->validated('approver_user_id'),
            pin: (string) $request->validated('pin'),
            reason: (string) $request->validated('reason'),
        );

        return new JsonResponse([
            'message' => 'Transaksi pesanan berhasil dibatalkan (void).',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'void_reason' => $order->void_reason,
                'voided_by_user_id' => $order->voided_by_user_id,
                'voided_at' => $order->voided_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * proses pengembalian dana pesanan (refund) dengan otorisasi PIN approver
     */
    public function refundOrder(RefundOrderRequest $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        $refundAmount = $request->validated('refund_amount') !== null
            ? (float) $request->validated('refund_amount')
            : null;

        $order = $this->posService->refundOrder(
            workspaceId: $workspaceId,
            branchId: $branchId,
            orderId: $id,
            approverUserId: (string) $request->validated('approver_user_id'),
            pin: (string) $request->validated('pin'),
            reason: (string) $request->validated('reason'),
            refundAmount: $refundAmount,
            refundMethod: (string) $request->validated('refund_method'),
        );

        return new JsonResponse([
            'message' => 'Pengembalian dana (refund) transaksi berhasil diproses.',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'refund_amount' => (float) $order->refund_amount,
                'refund_reason' => $order->refund_reason,
                'refund_method' => $order->refund_method,
                'refunded_in_session_id' => $order->refunded_in_session_id,
                'refunded_by_user_id' => $order->refunded_by_user_id,
                'refunded_at' => $order->refunded_at?->toIso8601String(),
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
                    'id' => (string) $o->id,
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
                    'payment_status' => $o->payment_status ?? 'PAID',
                    'void_reason' => $o->void_reason,
                    'voided_at' => $o->voided_at?->toIso8601String(),
                    'refund_amount' => (float) ($o->refund_amount ?? 0),
                    'refund_reason' => $o->refund_reason,
                    'refund_method' => $o->refund_method,
                    'refunded_at' => $o->refunded_at?->toIso8601String(),
                    'cash_tendered' => $o->cash_tendered ? (float) $o->cash_tendered : null,
                    'change_amount' => $o->change_amount ? (float) $o->change_amount : null,
                    'items' => $o->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'product_name' => $i->product_name,
                        'quantity' => (int) $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                        'subtotal' => (float) $i->subtotal,
                        'notes' => $i->notes,
                        'modifiers' => $i->modifiers,
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
     * ambil riwayat catatan belanja outlet pada cabang ini
     */
    public function purchases(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');
        $sessionId = $request->query('pos_session_id');

        $purchases = $this->posService->getOutletPurchases(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posSessionId: $sessionId ? (string) $sessionId : null,
        );

        return new JsonResponse([
            'message' => 'Riwayat pengeluaran belanja outlet berhasil dimuat.',
            'data' => $purchases,
        ], Response::HTTP_OK);
    }

    /**
     * catat pengeluaran belanja operasional / kas laci dari perangkat POS
     */
    public function storePurchase(\App\Http\Requests\Pos\CreateOutletPurchaseRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = (string) $request->attributes->get('current_branch_id');

        // temukan kasir penanggung jawab
        $cashierUserId = $request->validated('cashier_user_id');
        if (! $cashierUserId) {
            $firstMember = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)->first();
            $cashierUserId = $firstMember?->user_id;
        }

        $pin = $request->validated('pin');
        if ($cashierUserId && $pin) {
            $member = \App\Models\WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', $cashierUserId)
                ->first();

            if (! $member || empty($member->pin) || ! \Illuminate\Support\Facades\Hash::check((string) $pin, (string) $member->pin)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pin' => ['PIN kasir tidak valid untuk otorisasi pengeluaran kas.'],
                ]);
            }
        }

        // jika pos_session_id tidak diberikan, coba cari sesi aktif yang sedang buka di cabang ini
        $posSessionId = $request->validated('pos_session_id');
        if (! $posSessionId) {
            $activeSession = \App\Models\PosSession::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->where('status', 'OPEN')
                ->latest('opened_at')
                ->first();
            $posSessionId = $activeSession?->id;
        }

        $purchase = $this->posService->createOutletPurchase(
            workspaceId: $workspaceId,
            branchId: $branchId,
            posSessionId: $posSessionId,
            recordedByUserId: (string) $cashierUserId,
            data: $request->validated(),
        );

        return new JsonResponse([
            'message' => 'Pengeluaran belanja outlet berhasil dicatat.',
            'data' => $purchase->load(['recordedByUser:id,name', 'branch:id,name']),
        ], Response::HTTP_CREATED);
    }
}
