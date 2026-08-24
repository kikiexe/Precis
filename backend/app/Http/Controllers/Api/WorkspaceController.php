<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {
    }

    /**
     * buat workspace bisnis baru oleh user yang terautentikasi (otomatis role OWNER)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $result = $this->workspaceService->createWorkspace(
            $user,
            $validated['name'],
            $validated['branch_name'] ?? null
        );

        return response()->json([
            'message' => 'Workspace bisnis baru berhasil dibuat.',
            'workspace' => [
                'id' => $result['workspace']->id,
                'name' => $result['workspace']->name,
                'slug' => $result['workspace']->slug,
            ],
            'branch' => [
                'id' => $result['branch']->id,
                'name' => $result['branch']->name,
            ],
            'role' => $result['member']->role,
            'workspaces' => $result['workspaces'],
        ], 201);
    }
}
