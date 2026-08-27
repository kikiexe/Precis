<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WorkspaceRole;
use App\Models\WorkspaceRolePermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * Membuat role default bawaan untuk workspace baru (opsional)
     */
    public function createDefaultRolesForWorkspace(string $workspaceId): void
    {
        // Workspaces start clean without forced auto-generated roles.
    }

    /**
     * Mengambil katalog seluruh modul hak akses dan preset yang tersedia di sistem
     *
     * @return array{modules: array<string, mixed>, presets: array<string, mixed>}
     */
    public function getPermissionsCatalog(): array
    {
        return [
            'modules' => config('permissions.modules', []),
            'presets' => config('permissions.presets', []),
        ];
    }

    /**
     * Mendapatkan daftar seluruh role dalam workspace beserta daftar permission dan jumlah anggota
     */
    public function listRoles(string $workspaceId): Collection
    {
        return WorkspaceRole::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['permissions'])
            ->withCount('members')
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Mengambil detail satu role
     */
    public function getRole(string $workspaceId, string $roleId): WorkspaceRole
    {
        $role = WorkspaceRole::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $roleId)
            ->with(['permissions'])
            ->withCount('members')
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role_id' => ['Peran (role) tidak ditemukan pada workspace ini.'],
            ]);
        }

        return $role;
    }

    /**
     * Membuat custom role baru dalam workspace
     *
     * @param array{name: string, description?: string|null, permissions: array<int, string>} $data
     */
    public function createRole(string $workspaceId, array $data): WorkspaceRole
    {
        $name = trim($data['name']);

        // Validasi nama tidak boleh sama dengan OWNER
        if (strtoupper($name) === 'OWNER') {
            throw ValidationException::withMessages([
                'name' => ['Nama peran "OWNER" dilindungi sistem dan tidak dapat dibuat manual.'],
            ]);
        }

        // Validasi keunikan nama peran di workspace ini
        $exists = WorkspaceRole::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Peran dengan nama "' . $name . '" sudah ada di bisnis Anda.'],
            ]);
        }

        return DB::transaction(function () use ($workspaceId, $data, $name): WorkspaceRole {
            $role = WorkspaceRole::withoutGlobalScopes()->create([
                'workspace_id' => $workspaceId,
                'name' => $name,
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);

            $permissions = array_unique($data['permissions'] ?? []);
            foreach ($permissions as $perm) {
                if (! empty($perm)) {
                    WorkspaceRolePermission::create([
                        'role_id' => $role->id,
                        'permission' => $perm,
                    ]);
                }
            }

            return $this->getRole($workspaceId, $role->id);
        });
    }

    /**
     * Memperbarui peran dan checklist hak aksesnya
     *
     * @param array{name?: string, description?: string|null, permissions?: array<int, string>} $data
     */
    public function updateRole(string $workspaceId, string $roleId, array $data): WorkspaceRole
    {
        $role = $this->getRole($workspaceId, $roleId);

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (strtoupper($name) === 'OWNER') {
                throw ValidationException::withMessages([
                    'name' => ['Nama peran "OWNER" dilindungi sistem.'],
                ]);
            }

            // Cek keunikan jika nama diubah
            if (strtolower($role->name) !== strtolower($name)) {
                $exists = WorkspaceRole::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)
                    ->where('id', '!=', $roleId)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'name' => ['Peran dengan nama "' . $name . '" sudah digunakan.'],
                    ]);
                }
                $role->name = $name;
            }
        }

        if (array_key_exists('description', $data)) {
            $role->description = $data['description'];
        }

        return DB::transaction(function () use ($role, $data, $workspaceId, $roleId): WorkspaceRole {
            $role->save();

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                // Hapus dan pasang ulang permission baru
                WorkspaceRolePermission::where('role_id', $role->id)->delete();
                $permissions = array_unique($data['permissions']);
                foreach ($permissions as $perm) {
                    if (! empty($perm)) {
                        WorkspaceRolePermission::create([
                            'role_id' => $role->id,
                            'permission' => $perm,
                        ]);
                    }
                }
            }

            return $this->getRole($workspaceId, $roleId);
        });
    }

    /**
     * Menghapus custom role
     */
    public function deleteRole(string $workspaceId, string $roleId): void
    {
        $role = $this->getRole($workspaceId, $roleId);

        if ($role->members_count > 0) {
            throw ValidationException::withMessages([
                'role_id' => ["Peran ini masih digunakan oleh {$role->members_count} anggota tim. Pindahkan posisi anggota sebelum menghapus peran ini."],
            ]);
        }

        DB::transaction(function () use ($role): void {
            WorkspaceRolePermission::where('role_id', $role->id)->delete();
            $role->delete();
        });
    }
}
