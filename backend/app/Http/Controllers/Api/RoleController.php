<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Workspace;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * Mendapatkan daftar peran (roles) di workspace aktif
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('current_workspace');
        $roles = $this->roleService->listRoles($workspace->id);

        return response()->json([
            'roles' => $roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'is_system' => $r->is_system,
                'members_count' => $r->members_count,
                'permissions' => $r->permissions->pluck('permission')->toArray(),
                'created_at' => $r->created_at?->toIso8601String(),
                'updated_at' => $r->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Mengambil katalog modul hak akses & template preset
     */
    public function catalog(): JsonResponse
    {
        $catalog = $this->roleService->getPermissionsCatalog();

        return response()->json($catalog);
    }

    /**
     * Membuat custom role baru
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('current_workspace');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'max:60'],
        ], [
            'name.required' => 'Nama peran wajib diisi.',
            'name.max' => 'Nama peran maksimal 50 karakter.',
            'permissions.required' => 'Setidaknya pilih satu hak akses untuk peran ini.',
            'permissions.array' => 'Format izin hak akses tidak valid.',
        ]);

        $role = $this->roleService->createRole($workspace->id, $validated);

        return response()->json([
            'message' => 'Peran kustom "' . $role->name . '" berhasil dibuat.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'members_count' => $role->members_count,
                'permissions' => $role->permissions->pluck('permission')->toArray(),
                'created_at' => $role->created_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Mengambil detail satu role
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('current_workspace');
        $role = $this->roleService->getRole($workspace->id, $id);

        return response()->json([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'members_count' => $role->members_count,
                'permissions' => $role->permissions->pluck('permission')->toArray(),
            ],
        ]);
    }

    /**
     * Memperbarui peran dan checklist izin
     */
    public function update(Request $request, string $id): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('current_workspace');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'max:60'],
        ]);

        $role = $this->roleService->updateRole($workspace->id, $id, $validated);

        return response()->json([
            'message' => 'Peran "' . $role->name . '" berhasil diperbarui.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'members_count' => $role->members_count,
                'permissions' => $role->permissions->pluck('permission')->toArray(),
                'updated_at' => $role->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Menghapus peran kustom
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('current_workspace');
        $this->roleService->deleteRole($workspace->id, $id);

        return response()->json([
            'message' => 'Peran kustom berhasil dihapus.',
        ]);
    }
}
