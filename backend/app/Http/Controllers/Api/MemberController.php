<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MemberController
{
    /**
     * ambil daftar seluruh karyawan (member) dalam workspace
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $members = WorkspaceMember::withoutGlobalScopes()
            ->with(['user', 'branch', 'customRole.permissions'])
            ->where('workspace_id', $workspaceId)
            ->orderByRaw("CASE WHEN role = 'OWNER' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (WorkspaceMember $member): array {
                $isOwner = $member->role === 'OWNER';
                $roleName = $member->customRole?->name ?? ($isOwner ? 'Pemilik Usaha' : $member->role);

                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name ?? 'Tanpa Nama',
                    'email' => $member->user?->email ?? '-',
                    'job_title' => $member->job_title ?: ($isOwner ? 'Pemilik Usaha' : 'Staf'),
                    'role' => $member->role,
                    'role_id' => $member->role_id,
                    'role_name' => $roleName,
                    'permissions' => $isOwner ? ['*'] : ($member->customRole ? $member->customRole->permissions->pluck('permission')->toArray() : []),
                    'branch_id' => $member->branch_id,
                    'branch_name' => $member->branch?->name ?? 'Semua Cabang',
                    'base_salary' => (float) $member->base_salary,
                    'is_active' => (bool) $member->is_active,
                    'created_at' => $member->created_at?->toISOString(),
                ];
            });

        return new JsonResponse([
            'message' => 'Daftar anggota tim berhasil dimuat.',
            'data' => $members,
        ], Response::HTTP_OK);
    }

    /**
     * tambahkan karyawan baru ke workspace
     */
    public function store(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $rawBranchId = $request->input('branch_id');
        $branchId = null;
        if ($rawBranchId && Str::isUuid((string) $rawBranchId)) {
            $branchExists = DB::table('branches')
                ->where('id', $rawBranchId)
                ->where('workspace_id', $workspaceId)
                ->exists();
            if ($branchExists) {
                $branchId = $rawBranchId;
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'job_title' => ['required', 'string', 'max:100'],
            'role' => ['sometimes', 'string', 'max:50'],
            'role_id' => ['nullable', 'string', 'uuid'],
            'base_salary' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated, $workspaceId, $branchId): JsonResponse {
            // Cari atau buat User
            $user = User::where('email', strtolower($validated['email']))->first();

            if (! $user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => strtolower($validated['email']),
                    'password' => Hash::make('Password123!'),
                    'subscription_status' => 'ACTIVE',
                    'max_workspaces' => 1,
                ]);
            }

            // Periksa apakah sudah menjadi anggota
            $existing = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return new JsonResponse([
                    'message' => 'Karyawan dengan email ini sudah terdaftar sebagai anggota di workspace ini.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $roleId = $validated['role_id'] ?? null;
            $roleName = $validated['role'] ?? 'STAFF';

            if ($roleId) {
                $roleModel = WorkspaceRole::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)
                    ->find($roleId);
                if ($roleModel) {
                    $roleName = $roleModel->name;
                } else {
                    $roleId = null;
                }
            }

            $member = WorkspaceMember::withoutGlobalScopes()->create([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'role_id' => $roleId,
                'job_title' => $validated['job_title'],
                'role' => $roleName,
                'base_salary' => (float) $validated['base_salary'],
                'is_active' => true,
            ]);

            $member->load(['user', 'branch', 'customRole.permissions']);

            return new JsonResponse([
                'message' => "Karyawan {$user->name} ({$member->job_title}) berhasil ditambahkan. Kata sandi awal: Password123!",
                'data' => [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                    'job_title' => $member->job_title,
                    'role' => $member->role,
                    'role_id' => $member->role_id,
                    'role_name' => $member->customRole?->name ?? $member->role,
                    'branch_id' => $member->branch_id,
                    'branch_name' => $member->branch?->name ?? 'Semua Cabang',
                    'base_salary' => (float) $member->base_salary,
                    'is_active' => (bool) $member->is_active,
                    'created_at' => $member->created_at?->toISOString(),
                ],
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * perbarui data karyawan (job_title, role, role_id, branch, base_salary)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'job_title' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', 'string', 'max:50'],
            'role_id' => ['nullable', 'string', 'uuid'],
            'base_salary' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        /** @var WorkspaceMember $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (isset($validated['job_title'])) {
            $member->job_title = $validated['job_title'];
        }

        if ($member->role !== 'OWNER') {
            if ($request->has('role_id')) {
                $roleId = $validated['role_id'];
                if ($roleId) {
                    $roleModel = WorkspaceRole::withoutGlobalScopes()
                        ->where('workspace_id', $workspaceId)
                        ->find($roleId);
                    if ($roleModel) {
                        $member->role_id = $roleModel->id;
                        $member->role = $roleModel->name;
                    }
                } else {
                    $member->role_id = null;
                }
            } elseif (isset($validated['role'])) {
                $member->role = $validated['role'];
            }
        }

        if ($request->has('branch_id')) {
            $rawBranchId = $request->input('branch_id');
            if ($rawBranchId && Str::isUuid((string) $rawBranchId)) {
                $branchExists = DB::table('branches')
                    ->where('id', $rawBranchId)
                    ->where('workspace_id', $workspaceId)
                    ->exists();
                $member->branch_id = $branchExists ? $rawBranchId : null;
            } else {
                $member->branch_id = null;
            }
        }

        if (isset($validated['base_salary'])) {
            $member->base_salary = (float) $validated['base_salary'];
        }

        if (isset($validated['is_active'])) {
            $member->is_active = (bool) $validated['is_active'];
        }

        $member->save();
        $member->load(['user', 'branch', 'customRole.permissions']);

        return new JsonResponse([
            'message' => "Data karyawan {$member->user?->name} berhasil diperbarui.",
            'data' => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'job_title' => $member->job_title,
                'role' => $member->role,
                'role_id' => $member->role_id,
                'role_name' => $member->customRole?->name ?? ($member->role === 'OWNER' ? 'Pemilik Usaha' : $member->role),
                'branch_id' => $member->branch_id,
                'branch_name' => $member->branch?->name ?? 'Semua Cabang',
                'base_salary' => (float) $member->base_salary,
                'is_active' => (bool) $member->is_active,
                'created_at' => $member->created_at?->toISOString(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * hapus (nonaktifkan) karyawan dari workspace
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        /** @var WorkspaceMember $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if ($member->role === 'OWNER') {
            return new JsonResponse([
                'message' => 'Pemilik bisnis (OWNER) tidak dapat dihapus dari workspace.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $memberName = $member->user?->name ?? 'Karyawan';
        $member->delete();

        return new JsonResponse([
            'message' => "Karyawan {$memberName} berhasil dihapus dari workspace.",
        ], Response::HTTP_OK);
    }
}
