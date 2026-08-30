<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pos\CreateOutletPurchaseRequest;
use App\Models\Branch;
use App\Models\OutletPurchase;
use App\Models\PosSession;
use App\Models\WorkspaceMember;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OutletPurchaseController
{
    public function __construct(
        private readonly PosService $posService,
    ) {
    }

    /**
     * ambil daftar belanja operasional outlet dengan filter cabang dan pagination
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user?->id)
            ->first();

        $branchId = $request->query('branch_id');

        // isolasi cabang: jika bukan owner dan memiliki branch_id khusus, kunci ke cabang tersebut
        if ($member && $member->role !== 'OWNER' && $member->branch_id) {
            $branchId = $member->branch_id;
        }

        $sessionId = $request->query('pos_session_id');

        $purchases = $this->posService->getOutletPurchases(
            workspaceId: $workspaceId,
            branchId: $branchId ? (string) $branchId : null,
            posSessionId: $sessionId ? (string) $sessionId : null,
        );

        return new JsonResponse([
            'message' => 'Daftar pengeluaran belanja outlet berhasil dimuat.',
            'data' => $purchases,
        ], Response::HTTP_OK);
    }

    /**
     * catat belanja operasional baru dari web portal admin
     */
    public function store(CreateOutletPurchaseRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user?->id)
            ->first();

        $branchId = $request->input('branch_id');
        if ($member && $member->role !== 'OWNER' && $member->branch_id) {
            $branchId = $member->branch_id;
        }

        if (! $branchId) {
            $defaultBranch = Branch::where('workspace_id', $workspaceId)->first();
            $branchId = $defaultBranch?->id;
        }

        if (! $branchId) {
            return new JsonResponse([
                'message' => 'Cabang outlet tidak ditemukan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $purchase = $this->posService->createOutletPurchase(
            workspaceId: $workspaceId,
            branchId: (string) $branchId,
            posSessionId: $request->validated('pos_session_id'),
            recordedByUserId: (string) $user->id,
            data: $request->validated(),
        );

        return new JsonResponse([
            'message' => 'Belanja outlet berhasil dicatat.',
            'data' => $purchase->load(['recordedByUser:id,name', 'branch:id,name']),
        ], Response::HTTP_CREATED);
    }

    /**
     * tampilkan detail catatan belanja outlet
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user?->id)
            ->first();

        /** @var OutletPurchase|null $purchase */
        $purchase = OutletPurchase::withoutGlobalScopes()
            ->with(['recordedByUser:id,name', 'branch:id,name', 'session'])
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->first();

        if (! $purchase) {
            return new JsonResponse([
                'message' => 'Catatan belanja outlet tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($member && $member->role !== 'OWNER' && $member->branch_id && $member->branch_id !== $purchase->branch_id) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki akses ke cabang outlet ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'message' => 'Detail belanja outlet berhasil dimuat.',
            'data' => $purchase,
        ], Response::HTTP_OK);
    }

    /**
     * hapus catatan belanja outlet
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user?->id)
            ->first();

        /** @var OutletPurchase|null $purchase */
        $purchase = OutletPurchase::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->first();

        if (! $purchase) {
            return new JsonResponse([
                'message' => 'Catatan belanja outlet tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($member && $member->role !== 'OWNER' && $member->branch_id && $member->branch_id !== $purchase->branch_id) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki akses untuk menghapus data cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $purchase->delete();

        return new JsonResponse([
            'message' => 'Catatan belanja outlet berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
