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

            $branch = Branch::create([
                'workspace_id' => $workspace->id,
                'name' => $branchName && trim($branchName) !== '' ? trim($branchName) : 'Cabang Utama #01',
                'lat' => -7.7829,
                'lng' => 110.3671,
                'radius_meters' => 50,
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
