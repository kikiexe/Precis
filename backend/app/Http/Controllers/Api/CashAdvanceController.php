<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\CashAdvance\CreateCashAdvanceRequest;
use App\Models\User;
use App\Services\CashAdvanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashAdvanceController
{
    public function __construct(
        private readonly CashAdvanceService $cashAdvanceService,
    ) {
    }

    /**
     * ajukan permohonan kasbon baru oleh staf
     */
    public function create(CreateCashAdvanceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $advance = $this->cashAdvanceService->requestCashAdvance(
            user: $user,
            workspaceId: $workspaceId,
            amount: (float) $request->validated('amount'),
        );

        return new JsonResponse([
            'message' => 'Permohonan kasbon berhasil diajukan.',
            'data' => [
                'id' => $advance->id,
                'amount' => (float) $advance->amount,
                'request_date' => $advance->request_date instanceof \DateTimeInterface
                    ? $advance->request_date->format('Y-m-d')
                    : (string) $advance->request_date,
                'status' => $advance->status,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * ambil riwayat kasbon pribadi staf yang sedang login
     */
    public function my(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $list = $this->cashAdvanceService->getMyCashAdvances($user, $workspaceId);

        return new JsonResponse([
            'message' => 'Riwayat kasbon berhasil dimuat.',
            'data' => $list,
        ], Response::HTTP_OK);
    }

    /**
     * ambil antrean pengajuan kasbon seluruh staf (khusus OWNER dan ADMIN)
     */
    public function adminList(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $status = $request->query('status');
        $branchId = $request->query('branch_id');

        $list = $this->cashAdvanceService->getAdminCashAdvances(
            workspaceId: $workspaceId,
            status: $status ? (string) $status : null,
            branchId: $branchId ? (string) $branchId : null,
        );

        return new JsonResponse([
            'message' => 'Daftar pengajuan kasbon berhasil dimuat.',
            'data' => $list,
        ], Response::HTTP_OK);
    }

    /**
     * setujui permohonan kasbon staf (khusus OWNER dan ADMIN)
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $advance = $this->cashAdvanceService->approveCashAdvance(
            approver: $user,
            workspaceId: $workspaceId,
            cashAdvanceId: $id,
        );

        return new JsonResponse([
            'message' => 'Permohonan kasbon berhasil disetujui.',
            'data' => [
                'id' => $advance->id,
                'amount' => (float) $advance->amount,
                'status' => $advance->status,
                'approved_by_user_id' => $advance->approved_by_user_id,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * tolak permohonan kasbon staf (khusus OWNER dan ADMIN)
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $advance = $this->cashAdvanceService->rejectCashAdvance(
            approver: $user,
            workspaceId: $workspaceId,
            cashAdvanceId: $id,
        );

        return new JsonResponse([
            'message' => 'Permohonan kasbon telah ditolak.',
            'data' => [
                'id' => $advance->id,
                'amount' => (float) $advance->amount,
                'status' => $advance->status,
                'approved_by_user_id' => $advance->approved_by_user_id,
            ],
        ], Response::HTTP_OK);
    }
}
