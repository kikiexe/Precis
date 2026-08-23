<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffAndShiftSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();
        $branchSleman = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->firstOrFail();
        $branchMalioboro = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->where('name', 'like', '%Malioboro%')->firstOrFail();

        // 1. Shift Templates for Sleman
        $shiftPagiSleman = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchSleman->id,
                'name' => 'Shift Pagi (Sleman)',
            ],
            [
                'expected_clock_in' => '07:00:00',
                'expected_clock_out' => '15:00:00',
            ]
        );

        $shiftSoreSleman = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchSleman->id,
                'name' => 'Shift Sore (Sleman)',
            ],
            [
                'expected_clock_in' => '15:00:00',
                'expected_clock_out' => '23:00:00',
            ]
        );

        // Shift Templates for Malioboro
        $shiftPagiMalioboro = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchMalioboro->id,
                'name' => 'Shift Pagi (Malioboro)',
            ],
            [
                'expected_clock_in' => '08:00:00',
                'expected_clock_out' => '16:00:00',
            ]
        );

        $shiftSoreMalioboro = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'branch_id' => $branchMalioboro->id,
                'name' => 'Shift Sore (Malioboro)',
            ],
            [
                'expected_clock_in' => '16:00:00',
                'expected_clock_out' => '00:00:00',
            ]
        );

        // 2. Staff: Store Manager Sleman
        $manager = User::firstOrCreate(
            ['email' => 'budi.manager@amorecoffee.id'],
            [
                'name' => 'Budi Santoso (Store Manager)',
                'password' => Hash::make('BudiManager2026!'),
                'subscription_status' => 'ACTIVE',
            ]
        );

        WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $manager->id,
            ],
            [
                'branch_id' => $branchSleman->id,
                'role' => 'ADMIN',
                'pin' => Hash::make('1234'),
                'base_salary' => 4500000.00,
                'is_active' => true,
            ]
        );

        // 3. Staff: Kasir 1 Sleman
        $kasir1 = User::firstOrCreate(
            ['email' => 'siti.kasir@amorecoffee.id'],
            [
                'name' => 'Siti Rahma (Barista/Kasir)',
                'password' => Hash::make('SitiKasir2026!'),
                'subscription_status' => 'ACTIVE',
            ]
        );

        WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $kasir1->id,
            ],
            [
                'branch_id' => $branchSleman->id,
                'role' => 'STAFF',
                'pin' => Hash::make('1122'),
                'base_salary' => 2800000.00,
                'is_active' => true,
            ]
        );

        // 4. Staff: Kasir 2 Malioboro
        $kasir2 = User::firstOrCreate(
            ['email' => 'dimas.kasir@amorecoffee.id'],
            [
                'name' => 'Dimas Pratama (Barista/Kasir)',
                'password' => Hash::make('DimasKasir2026!'),
                'subscription_status' => 'ACTIVE',
            ]
        );

        WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $kasir2->id,
            ],
            [
                'branch_id' => $branchMalioboro->id,
                'role' => 'STAFF',
                'pin' => Hash::make('3344'),
                'base_salary' => 2800000.00,
                'is_active' => true,
            ]
        );

        // 5. Shift Assignments for Today
        ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'shift_template_id' => $shiftPagiSleman->id,
                'assigned_user_id' => $kasir1->id,
                'date' => now()->toDateString(),
            ],
            [
                'actual_user_id' => $kasir1->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'created_by_user_id' => $manager->id,
            ]
        );

        ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'shift_template_id' => $shiftPagiMalioboro->id,
                'assigned_user_id' => $kasir2->id,
                'date' => now()->toDateString(),
            ],
            [
                'actual_user_id' => $kasir2->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'created_by_user_id' => $manager->id,
            ]
        );
    }
}
