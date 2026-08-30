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
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');
        $requestedBranchId = $request->query('branch_id');

        $query = WorkspaceMember::withoutGlobalScopes()
            ->with(['user', 'branch', 'customRole.permissions'])
            ->where('workspace_id', $workspaceId);

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($requestedBranchId && $requestedBranchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Daftar anggota tim berhasil dimuat.',
                    'data' => [],
                ], Response::HTTP_OK);
            }
            $query->where('branch_id', $actorMember->branch_id);
        } elseif ($requestedBranchId) {
            $query->where('branch_id', $requestedBranchId);
        }

        $members = $query
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
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');

        $rawBranchId = $request->input('branch_id');
        $branchId = null;
        if (! empty($rawBranchId)) {
            if (! Str::isUuid((string) $rawBranchId)) {
                return new JsonResponse([
                    'message' => 'Format branch_id tidak valid.',
                    'errors' => [
                        'branch_id' => ['Format branch_id tidak valid.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $branchExists = DB::table('branches')
                ->where('id', $rawBranchId)
                ->where('workspace_id', $workspaceId)
                ->exists();

            if (! $branchExists) {
                return new JsonResponse([
                    'message' => 'Cabang tidak ditemukan pada workspace ini.',
                    'errors' => [
                        'branch_id' => ['Cabang tidak ditemukan pada workspace ini.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $branchId = (string) $rawBranchId;
        }

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if (! $branchId || $branchId !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Akses ditolak. Anda hanya berwenang mengelola staf pada cabang penugasan Anda.',
                ], Response::HTTP_FORBIDDEN);
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

        if ($request->has('role') && strtoupper(trim((string) $request->input('role'))) === 'OWNER') {
            return new JsonResponse([
                'message' => 'Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.',
                'errors' => [
                    'role' => ['Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.'],
                ],
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

        if (strtoupper(trim((string) $roleName)) === 'OWNER') {
            return new JsonResponse([
                'message' => 'Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.',
                'errors' => [
                    'role' => ['Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($validated, $workspaceId, $branchId, $roleId, $roleName): JsonResponse {
            // cari atau buat user
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

            // periksa apakah sudah menjadi anggota
            $existing = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return new JsonResponse([
                    'message' => 'Karyawan dengan email ini sudah terdaftar sebagai anggota di workspace ini.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');

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

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($member->branch_id !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Akses ditolak. Anda hanya berwenang mengelola staf pada cabang penugasan Anda.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        if ($request->has('role') && strtoupper(trim((string) $request->input('role'))) === 'OWNER') {
            return new JsonResponse([
                'message' => 'Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.',
                'errors' => [
                    'role' => ['Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
                        if (strtoupper(trim((string) $roleModel->name)) === 'OWNER') {
                            return new JsonResponse([
                                'message' => 'Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.',
                                'errors' => [
                                    'role' => ['Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.'],
                                ],
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
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
            if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
                if ($rawBranchId !== $actorMember->branch_id) {
                    return new JsonResponse([
                        'message' => 'Akses ditolak. Anda hanya berwenang mengelola staf pada cabang penugasan Anda.',
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            if (! empty($rawBranchId)) {
                if (! Str::isUuid((string) $rawBranchId)) {
                    return new JsonResponse([
                        'message' => 'Format branch_id tidak valid.',
                        'errors' => [
                            'branch_id' => ['Format branch_id tidak valid.'],
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $branchExists = DB::table('branches')
                    ->where('id', $rawBranchId)
                    ->where('workspace_id', $workspaceId)
                    ->exists();

                if (! $branchExists) {
                    return new JsonResponse([
                        'message' => 'Cabang tidak ditemukan pada workspace ini.',
                        'errors' => [
                            'branch_id' => ['Cabang tidak ditemukan pada workspace ini.'],
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $member->branch_id = (string) $rawBranchId;
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
        /** @var WorkspaceMember|null $actorMember */
        $actorMember = $request->attributes->get('current_member');

        /** @var WorkspaceMember $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if ($member->role === 'OWNER') {
            return new JsonResponse([
                'message' => 'Pemilik bisnis (OWNER) tidak dapat dihapus dari workspace.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
            if ($member->branch_id !== $actorMember->branch_id) {
                return new JsonResponse([
                    'message' => 'Akses ditolak. Anda hanya berwenang mengelola staf pada cabang penugasan Anda.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $memberName = $member->user?->name ?? 'Karyawan';
        $member->delete();

        return new JsonResponse([
            'message' => "Karyawan {$memberName} berhasil dihapus dari workspace.",
        ], Response::HTTP_OK);
    }
}
