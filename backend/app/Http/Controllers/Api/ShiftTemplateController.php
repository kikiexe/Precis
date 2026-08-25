<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftTemplateController
{
    /**
     * Ambil daftar template shift untuk workspace aktif
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = $request->query('branch_id');

        $query = ShiftTemplate::where('workspace_id', $workspaceId);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
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
     * Buat template shift baru (khusus OWNER dan ADMIN)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expected_clock_in' => 'required|date_format:H:i',
            'expected_clock_out' => 'required|date_format:H:i',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = $validated['branch_id'] ?? $request->attributes->get('current_member')?->branch_id;

        // Jika branch_id masih kosong, ambil cabang pertama dari workspace
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
     * Hapus template shift
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $template = ShiftTemplate::where('workspace_id', $workspaceId)->findOrFail($id);
        $template->delete();

        return new JsonResponse([
            'message' => 'Template shift berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
