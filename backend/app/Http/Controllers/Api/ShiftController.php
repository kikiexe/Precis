<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Shift\AssignShiftRequest;
use App\Http\Requests\Shift\RequestSwapRequest;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftController
{
    public function __construct(
        private readonly ShiftService $shiftService,
    ) {
    }

    /**
     * ambil kalender jadwal roster shift per cabang
     */
    public function roster(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var \App\Models\WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($branchId && $branchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Kalender roster shift berhasil dimuat.',
                    'data' => [],
                ], Response::HTTP_OK);
            }
            $branchId = $actorMember->branch_id;
        }

        $roster = $this->shiftService->getRoster(
            workspaceId: $workspaceId,
            branchId: $branchId ? (string) $branchId : null,
            startDate: $startDate ? (string) $startDate : null,
            endDate: $endDate ? (string) $endDate : null,
        );

        return new JsonResponse([
            'message' => 'Kalender roster shift berhasil dimuat.',
            'data' => $roster,
        ], Response::HTTP_OK);
    }

    /**
     * tetapkan jadwal shift staf (khusus OWNER dan ADMIN)
     */
    public function assign(AssignShiftRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $assignment = $this->shiftService->assignShift(
            creator: $user,
            workspaceId: $workspaceId,
            shiftTemplateId: (string) $request->validated('shift_template_id'),
            assignedUserId: (string) $request->validated('assigned_user_id'),
            date: (string) $request->validated('date'),
        );

        return new JsonResponse([
            'message' => 'Jadwal shift berhasil ditetapkan.',
            'data' => [
                'id' => $assignment->id,
                'shift_template_id' => $assignment->shift_template_id,
                'assigned_user_id' => $assignment->assigned_user_id,
                'date' => $assignment->date instanceof \DateTimeInterface ? $assignment->date->format('Y-m-d') : (string) $assignment->date,
                'is_swap' => $assignment->is_swap,
                'swap_status' => $assignment->swap_status,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * hapus atau batalkan penugasan shift (menjadikan libur / off)
     */
    public function deleteAssignment(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var User $user */
        $user = $request->user();

        $this->shiftService->deleteAssignment(
            workspaceId: $workspaceId,
            assignmentId: $id,
            actor: $user,
        );

        return new JsonResponse([
            'message' => 'Penugasan shift berhasil dibatalkan.',
        ], Response::HTTP_OK);
    }

    /**
     * ajukan permohonan pertukaran shift oleh staf
     */
    public function requestSwap(RequestSwapRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $assignment = $this->shiftService->requestSwap(
            requester: $user,
            workspaceId: $workspaceId,
            shiftAssignmentId: (string) $request->validated('shift_assignment_id'),
            targetUserId: (string) $request->validated('target_user_id'),
        );

        return new JsonResponse([
            'message' => 'Permohonan tukar shift berhasil diajukan.',
            'data' => [
                'id' => $assignment->id,
                'assigned_user_id' => $assignment->assigned_user_id,
                'actual_user_id' => $assignment->actual_user_id,
                'is_swap' => $assignment->is_swap,
                'swap_status' => $assignment->swap_status,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil antrean permohonan tukar shift yang tertunda (khusus OWNER dan ADMIN)
     */
    public function pendingSwapRequests(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var \App\Models\WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');
        $branchId = $request->query('branch_id');

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($branchId && $branchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Daftar permohonan tukar shift berhasil dimuat.',
                    'data' => [],
                ], Response::HTTP_OK);
            }
            $branchId = $actorMember->branch_id;
        }

        $requests = $this->shiftService->getPendingSwapRequests(
            workspaceId: $workspaceId,
            branchId: $branchId ? (string) $branchId : null,
        );

        return new JsonResponse([
            'message' => 'Daftar permohonan tukar shift berhasil dimuat.',
            'data' => $requests,
        ], Response::HTTP_OK);
    }

    /**
     * setujui permohonan tukar shift (khusus OWNER dan ADMIN)
     */
    public function approveSwap(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $assignment = $this->shiftService->approveSwap(
            approver: $user,
            workspaceId: $workspaceId,
            shiftAssignmentId: $id,
        );

        return new JsonResponse([
            'message' => 'Permohonan tukar shift berhasil disetujui.',
            'data' => [
                'id' => $assignment->id,
                'assigned_user_id' => $assignment->assigned_user_id,
                'actual_user_id' => $assignment->actual_user_id,
                'swap_status' => $assignment->swap_status,
                'swap_approved_by_user_id' => $assignment->swap_approved_by_user_id,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * tolak permohonan tukar shift (khusus OWNER dan ADMIN)
     */
    public function rejectSwap(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $assignment = $this->shiftService->rejectSwap(
            approver: $user,
            workspaceId: $workspaceId,
            shiftAssignmentId: $id,
        );

        return new JsonResponse([
            'message' => 'Permohonan tukar shift telah ditolak.',
            'data' => [
                'id' => $assignment->id,
                'assigned_user_id' => $assignment->assigned_user_id,
                'actual_user_id' => $assignment->actual_user_id,
                'swap_status' => $assignment->swap_status,
                'swap_approved_by_user_id' => $assignment->swap_approved_by_user_id,
            ],
        ], Response::HTTP_OK);
    }
}
