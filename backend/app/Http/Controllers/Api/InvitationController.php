<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\InviteMemberRequest;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvitationController
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {
    }

    /**
     * ambil daftar seluruh undangan yang masih berstatus PENDING di workspace
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $invitations = $this->invitationService->getPendingInvitations($workspaceId);

        $data = $invitations->map(function ($inv): array {
            return [
                'id' => $inv->id,
                'email' => $inv->email,
                'job_title' => $inv->job_title,
                'role' => $inv->role,
                'base_salary' => (float) $inv->base_salary,
                'branch_id' => $inv->branch_id,
                'branch_name' => $inv->branch?->name ?? 'Semua Cabang',
                'invited_by_name' => $inv->invitedBy?->name ?? 'Admin',
                'status' => $inv->status,
                'expires_at' => $inv->expires_at?->toISOString(),
                'created_at' => $inv->created_at?->toISOString(),
            ];
        });

        return new JsonResponse([
            'message' => 'Daftar undangan tim berhasil dimuat.',
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * kirim undangan bergabung ke alamat email calon staf
     */
    public function store(InviteMemberRequest $request): JsonResponse
    {
        /** @var User $inviter */
        $inviter = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $invitation = $this->invitationService->createInvitation(
            inviter: $inviter,
            workspaceId: $workspaceId,
            data: $request->validated(),
        );

        return new JsonResponse([
            'message' => "Undangan berhasil dikirimkan ke {$invitation->email}.",
            'data' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'job_title' => $invitation->job_title,
                'role' => $invitation->role,
                'base_salary' => (float) $invitation->base_salary,
                'branch_id' => $invitation->branch_id,
                'branch_name' => $invitation->branch?->name ?? 'Semua Cabang',
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at?->toISOString(),
                'created_at' => $invitation->created_at?->toISOString(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * batalkan undangan tim
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $this->invitationService->cancelInvitation($workspaceId, $id);

        return new JsonResponse([
            'message' => 'Undangan berhasil dibatalkan.',
        ], Response::HTTP_OK);
    }

    /**
     * kirim ulang email undangan
     */
    public function resend(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $invitation = $this->invitationService->resendInvitation($workspaceId, $id);

        return new JsonResponse([
            'message' => "Email undangan ke {$invitation->email} berhasil dikirim ulang.",
            'data' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * endpoint publik untuk mengecek detail undangan berdasarkan token
     */
    public function show(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        return new JsonResponse([
            'message' => 'Detail undangan berhasil diverifikasi.',
            'data' => [
                'id' => $invitation->id,
                'workspace_name' => $invitation->workspace?->name,
                'workspace_slug' => $invitation->workspace?->slug,
                'invited_by_name' => $invitation->invitedBy?->name,
                'email' => $invitation->email,
                'job_title' => $invitation->job_title,
                'role' => $invitation->role,
                'branch_name' => $invitation->branch?->name ?? 'Semua Cabang',
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * endpoint publik / authenticated untuk menerima undangan
     */
    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $request->user();

        $result = $this->invitationService->acceptInvitation(
            token: $token,
            authenticatedUser: $currentUser,
            name: $request->validated('name'),
            password: $request->validated('password'),
        );

        return new JsonResponse([
            'message' => 'Selamat, Anda telah resmi bergabung ke dalam tim workspace.',
            'data' => [
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                ],
                'member' => [
                    'id' => $result['member']->id,
                    'workspace_id' => $result['member']->workspace_id,
                    'job_title' => $result['member']->job_title,
                    'role' => $result['member']->role,
                    'branch_id' => $result['member']->branch_id,
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * endpoint publik untuk menolak undangan
     */
    public function reject(string $token): JsonResponse
    {
        $this->invitationService->rejectInvitation($token);

        return new JsonResponse([
            'message' => 'Undangan tim telah ditolak.',
        ], Response::HTTP_OK);
    }
}
