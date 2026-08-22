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
}
