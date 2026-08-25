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
        $workspaceSeturan = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $workspaceKaliurang = Workspace::where('slug', 'norde-coffee-kaliurang')->first() ?? $workspaceSeturan;

        // 1. Cabang 1: Norde Coffee - Seturan #01 (pada Workspace 1)
        $branchSeturan = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Norde Coffee - Seturan #01',
            ],
            [
                'lat' => -7.76543000,
                'lng' => 110.40912000,
                'radius_meters' => 50,
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
            ]
        );

        PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
                'terminal_name' => 'Norde POS Tab Seturan #01',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-seturan-01'),
                'is_active' => true,
            ]
        );

        // 2. Cabang 2: Norde Coffee - Kaliurang #02 (pada Workspace 1 untuk multi-branch support)
        $branchKaliurangSeturan = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Norde Coffee - Kaliurang #02',
            ],
            [
                'lat' => -7.72145000,
                'lng' => 110.39567000,
                'radius_meters' => 60,
            ]
        );

        BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchKaliurangSeturan->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
            ]
        );

        PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchKaliurangSeturan->id,
                'terminal_name' => 'Norde POS Tab Kaliurang #01',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-kaliurang-01'),
                'is_active' => true,
            ]
        );

        // 3. Cabang 2 pada Workspace 2 (jika workspace 2 terpisah)
        if ($workspaceKaliurang->id !== $workspaceSeturan->id) {
            $branchKaliurangWS2 = Branch::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'name' => 'Norde Coffee - Kaliurang #02',
                ],
                [
                    'lat' => -7.72145000,
                    'lng' => 110.39567000,
                    'radius_meters' => 60,
                ]
            );

            BranchSetting::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'branch_id' => $branchKaliurangWS2->id,
                ],
                [
                    'late_penalty_per_minute' => 1000.00,
                    'overtime_pay_per_hour' => 20000.00,
                    'min_overtime_threshold_minutes' => 30,
                ]
            );

            PosTerminal::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'branch_id' => $branchKaliurangWS2->id,
                    'terminal_name' => 'Norde POS Tab Kaliurang #02',
                ],
                [
                    'device_token_hash' => hash('sha256', 'pos-device-token-kaliurang-ws2'),
                    'is_active' => true,
                ]
            );
        }
    }
}
