<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\PosTerminal;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();

        // 1. Outlet Sleman #01
        $branchSleman = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'name' => 'Amore Outlet Sleman #01',
            ],
            [
                'lat' => -7.71234000,
                'lng' => 110.35467000,
                'radius_meters' => 50,
            ]
        );

        // BranchSetting Sleman
        BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchSleman->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
            ]
        );

        // POS Terminal Sleman
        PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchSleman->id,
                'terminal_name' => 'Amore POS Tab Sleman #01',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-sleman-01'),
                'is_active' => true,
            ]
        );

        // 2. Outlet Malioboro #02
        $branchMalioboro = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'name' => 'Amore Outlet Malioboro #02',
            ],
            [
                'lat' => -7.79256000,
                'lng' => 110.36589000,
                'radius_meters' => 60,
            ]
        );

        // BranchSetting Malioboro
        BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchMalioboro->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
            ]
        );

        // POS Terminal Malioboro
        PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchMalioboro->id,
                'terminal_name' => 'Amore POS Tab Malioboro #01',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-malioboro-01'),
                'is_active' => true,
            ]
        );
    }
}
