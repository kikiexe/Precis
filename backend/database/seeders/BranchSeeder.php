<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\PosTerminal;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $pilotCreationDate = (clone $now)->subYears(5)->startOfMonth();

        $workspaceSeturan = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $workspaceKaliurang = Workspace::where('slug', 'norde-coffee-kaliurang')->first() ?? $workspaceSeturan;

        // 1. Cabang 1: Norde Coffee - Seturan (pada Workspace 1: Seturan)
        $branchSeturan = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Norde Coffee - Seturan',
            ],
            [
                'lat' => -7.76543000,
                'lng' => 110.40912000,
                'radius_meters' => 50,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $terminalSeturan = PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
                'terminal_name' => 'Norde POS Tab Seturan',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-seturan-01'),
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // 2. Cabang 2: Norde Coffee - Kaliurang (pada Workspace 2: Kaliurang)
        $targetWsKaliurang = ($workspaceKaliurang->id !== $workspaceSeturan->id) ? $workspaceKaliurang : $workspaceSeturan;

        $branchKaliurang = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'name' => 'Norde Coffee - Kaliurang',
            ],
            [
                'lat' => -7.72145000,
                'lng' => 110.39567000,
                'radius_meters' => 60,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'branch_id' => $branchKaliurang->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $terminalKaliurang = PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'branch_id' => $branchKaliurang->id,
                'terminal_name' => 'Norde POS Tab Kaliurang',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-kaliurang-01'),
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
    }
}
