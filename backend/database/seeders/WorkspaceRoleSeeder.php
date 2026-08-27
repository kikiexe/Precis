<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Services\RoleService;
use Illuminate\Database\Seeder;

class WorkspaceRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roleService = new RoleService();
        $workspaces = Workspace::all();

        foreach ($workspaces as $workspace) {
            // 1. Buat 5 preset system roles bawaan untuk workspace ini
            $roleService->createDefaultRolesForWorkspace($workspace->id);

            // 2. Ambil role sistem bawaan
            $managerRole = WorkspaceRole::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->where('name', 'Manajer Operasional')
                ->first();

            $staffRole = WorkspaceRole::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->where('name', 'Karyawan / Barista')
                ->first();

            // 3. Hubungkan member yang ada ke role sistem yang sesuai
            if ($managerRole) {
                WorkspaceMember::withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->where('role', 'MANAGER')
                    ->whereNull('role_id')
                    ->update(['role_id' => $managerRole->id]);
            }

            if ($staffRole) {
                WorkspaceMember::withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->where('role', 'STAFF')
                    ->whereNull('role_id')
                    ->update(['role_id' => $staffRole->id]);
            }
        }
    }
}
