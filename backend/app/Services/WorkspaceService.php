<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceService
{
    public function __construct(
        private readonly RoleService $roleService = new RoleService()
    ) {}

    /**
     * buat workspace baru, cabang utama pertama, dan daftarkan pembuat sebagai OWNER
     *
     * @return array{workspace: Workspace, branch: Branch, member: WorkspaceMember, workspaces: array<int, mixed>}
     */
    public function createWorkspace(User $user, string $name, ?string $branchName = null): array
    {
        return DB::transaction(function () use ($user, $name, $branchName): array {
            $workspace = Workspace::create([
                'name' => trim($name),
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
                'owner_user_id' => $user->id,
            ]);

            // Seed default system roles untuk workspace baru ini
            $this->roleService->createDefaultRolesForWorkspace($workspace->id);

            $branch = Branch::create([
                'workspace_id' => $workspace->id,
                'name' => $branchName && trim($branchName) !== '' ? trim($branchName) : 'Cabang Utama #01',
                'lat' => -7.7829,
                'lng' => 110.3671,
                'radius_meters' => 50,
            ]);

            \App\Models\BranchSetting::create([
                'workspace_id' => $workspace->id,
                'branch_id' => $branch->id,
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
            ]);

            $rawToken = 'pos-' . Str::slug($branch->name) . '-' . Str::lower(Str::random(6));
            \App\Models\PosTerminal::create([
                'workspace_id' => $workspace->id,
                'branch_id' => $branch->id,
                'terminal_name' => 'Terminal Kasir Utama #01',
                'device_token' => $rawToken,
                'device_token_hash' => hash('sha256', $rawToken),
                'is_active' => true,
            ]);

            $member = WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => 'OWNER',
                'base_salary' => 0.00,
                'is_active' => true,
            ]);

            // Ambil semua daftar workspace yang diikuti user beserta perannya
            $allMemberships = WorkspaceMember::with('workspace')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->get();

            $workspaces = [];
            foreach ($allMemberships as $m) {
                if ($m->workspace) {
                    $primaryBranch = Branch::where('workspace_id', $m->workspace_id)->first();
                    $workspaces[] = [
                        'workspace_id' => $m->workspace->id,
                        'workspace_name' => $m->workspace->name,
                        'workspace_slug' => $m->workspace->slug,
                        'role' => $m->role,
                        'branch_id' => $m->branch_id ?? $primaryBranch?->id,
                        'branch_name' => $m->branch?->name ?? $primaryBranch?->name ?? $m->workspace->name,
                    ];
                }
            }

            return [
                'workspace' => $workspace,
                'branch' => $branch,
                'member' => $member,
                'workspaces' => $workspaces,
            ];
        });
    }
}
