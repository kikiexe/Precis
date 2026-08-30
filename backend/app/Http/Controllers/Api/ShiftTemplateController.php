<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\ShiftTemplate;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftTemplateController
{
    /**
     * ambil daftar template shift untuk workspace aktif
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');
        $branchId = $request->query('branch_id');

        $query = ShiftTemplate::where('workspace_id', $workspaceId);

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($branchId && $branchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Daftar template shift berhasil dimuat.',
                    'data' => [],
                ], Response::HTTP_OK);
            }
            $query->where(function ($q) use ($actorMember): void {
                $q->where('branch_id', $actorMember->branch_id)->orWhereNull('branch_id');
            });
        } elseif ($branchId) {
            $query->where(function ($q) use ($branchId): void {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        $templates = $query->orderBy('expected_clock_in')->get();

        return new JsonResponse([
            'message' => 'Daftar template shift berhasil dimuat.',
            'data' => $templates,
        ], Response::HTTP_OK);
    }

    /**
     * buat template shift baru (khusus OWNER dan ADMIN)
     */
    public function store(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expected_clock_in' => 'required|date_format:H:i',
            'expected_clock_out' => 'required|date_format:H:i',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $requestedBranchId = $validated['branch_id'] ?? null;

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($requestedBranchId && $requestedBranchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Akses ditolak. Anda hanya berwenang mengelola template shift pada cabang penugasan Anda.',
                ], Response::HTTP_FORBIDDEN);
            }
            $branchId = $actorMember->branch_id;
        } else {
            $branchId = $requestedBranchId ?? $actorMember?->branch_id;
        }

        // jika branch_id masih kosong, ambil cabang pertama dari workspace
        if (! $branchId) {
            $branchId = \App\Models\Branch::where('workspace_id', $workspaceId)->first()?->id;
        }

        if (! $branchId) {
            return new JsonResponse([
                'message' => 'Cabang outlet tidak ditemukan untuk workspace ini.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $template = ShiftTemplate::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'name' => $validated['name'],
            'expected_clock_in' => $validated['expected_clock_in'],
            'expected_clock_out' => $validated['expected_clock_out'],
        ]);

        return new JsonResponse([
            'message' => 'Template shift berhasil dibuat.',
            'data' => $template,
        ], Response::HTTP_CREATED);
    }

    /**
     * hapus template shift
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');

        $template = ShiftTemplate::where('workspace_id', $workspaceId)->findOrFail($id);

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($template->branch_id !== null && $template->branch_id !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Akses ditolak. Anda hanya berwenang mengelola template shift pada cabang penugasan Anda.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $template->delete();

        return new JsonResponse([
            'message' => 'Template shift berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
