<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashAdvance;
use App\Models\Payroll;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Models\WorkspaceRolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffAndShiftSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $pilotCreationDate = (clone $now)->subYears(5)->startOfMonth();

        $workspaceSeturan = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $workspaceKaliurang = Workspace::where('slug', 'norde-coffee-kaliurang')->first() ?? $workspaceSeturan;

        $branchSeturan = Branch::withoutGlobalScopes()->where('workspace_id', $workspaceSeturan->id)->where('name', 'like', '%Seturan%')->firstOrFail();
        $branchKaliurang = Branch::withoutGlobalScopes()->where('workspace_id', $workspaceKaliurang->id)->where('name', 'like', '%Kaliurang%')->firstOrFail();

        // 1. Shift Templates Seturan (WS 1)
        $shiftPagiSeturan = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
                'name' => 'Shift Pagi (Seturan)',
            ],
            [
                'expected_clock_in' => '07:00:00',
                'expected_clock_out' => '15:00:00',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $shiftSoreSeturan = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
                'name' => 'Shift Sore (Seturan)',
            ],
            [
                'expected_clock_in' => '15:00:00',
                'expected_clock_out' => '23:00:00',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // Shift Templates Kaliurang (WS 2)
        $shiftPagiKaliurang = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'branch_id' => $branchKaliurang->id,
                'name' => 'Shift Pagi (Kaliurang)',
            ],
            [
                'expected_clock_in' => '08:00:00',
                'expected_clock_out' => '16:00:00',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $shiftSoreKaliurang = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'branch_id' => $branchKaliurang->id,
                'name' => 'Shift Sore (Kaliurang)',
            ],
            [
                'expected_clock_in' => '16:00:00',
                'expected_clock_out' => '00:00:00',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // 2. Staff Users & Memberships
        // WS 1 (Seturan) - Manager: Paundra Mahendra
        $managerSeturan = User::firstOrCreate(
            ['email' => 'paundra@gmail.com'],
            [
                'name' => 'Paundra Mahendra',
                'password' => Hash::make('123456'),
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1370019283741',
                'bank_account_holder' => 'Paundra Mahendra',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $gmRoleSeturan = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'General Manager',
            ],
            [
                'description' => 'Akses penuh manajemen operasional, katalog menu, jadwal shift, dan persetujuan kasbon.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        foreach ([
            'catalog.view', 'catalog.manage',
            'inventory.view', 'inventory.adjust',
            'attendance.view_all', 'shifts.manage', 'shifts.approve_swap',
            'cash_advance.approve', 'members.view',
        ] as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $gmRoleSeturan->id,
                'permission' => $p,
            ]);
        }

        $headBaristaRoleSeturan = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Head Barista & Kasir',
            ],
            [
                'description' => 'Akses operasional kasir POS dan stok inventaris bar.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        foreach (['catalog.view', 'inventory.view', 'inventory.adjust'] as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $headBaristaRoleSeturan->id,
                'permission' => $p,
            ]);
        }

        $baristaRoleSeturan = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Barista & Kasir',
            ],
            [
                'description' => 'Akses operasional pesanan kasir POS dan inventaris bahan baku.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        foreach (['catalog.view', 'inventory.view'] as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $baristaRoleSeturan->id,
                'permission' => $p,
            ]);
        }

        $memberManagerSeturan = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $managerSeturan->id,
            ],
            [
                'branch_id' => $branchSeturan->id,
                'job_title' => 'General Manager',
                'role' => 'MANAGER',
                'role_id' => $gmRoleSeturan->id,
                'pin' => Hash::make('1234'),
                'base_salary' => 4500000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberManagerSeturan->update(['role_id' => $gmRoleSeturan->id]);

        // WS 1 (Seturan) - Staff 1: Ami
        $staffAmi = User::firstOrCreate(
            ['email' => 'ami@gmail.com'],
            [
                'name' => 'Siti Ami Rahmawati',
                'password' => Hash::make('123456'),
                'bank_name' => 'BRI',
                'bank_account_number' => '012301082736501',
                'bank_account_holder' => 'Siti Ami Rahmawati',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $memberAmi = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffAmi->id,
            ],
            [
                'branch_id' => $branchSeturan->id,
                'job_title' => 'Head Barista & Kasir',
                'role' => 'STAFF',
                'role_id' => $headBaristaRoleSeturan->id,
                'pin' => Hash::make('1122'),
                'base_salary' => 2800000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberAmi->update(['role_id' => $headBaristaRoleSeturan->id]);

        // WS 1 (Seturan) - Staff 2: Hani
        $staffHani = User::firstOrCreate(
            ['email' => 'hani@gmail.com'],
            [
                'name' => 'Hani Handayani',
                'password' => Hash::make('123456'),
                'bank_name' => 'BNI',
                'bank_account_number' => '0987654321',
                'bank_account_holder' => 'Hani Handayani',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $memberHani = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffHani->id,
            ],
            [
                'branch_id' => $branchSeturan->id,
                'job_title' => 'Barista & Kasir',
                'role' => 'STAFF',
                'role_id' => $baristaRoleSeturan->id,
                'pin' => Hash::make('2233'),
                'base_salary' => 2700000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberHani->update(['role_id' => $baristaRoleSeturan->id]);

        // Role WS 2 (Kaliurang)
        $gmFinanceRoleKaliurang = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'name' => 'General Manager & Finance',
            ],
            [
                'description' => 'Akses penuh operasional toko Kaliurang ditambah wewenang pencairan payroll.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $gmFinancePermissions = [
            'catalog.view', 'catalog.manage',
            'inventory.view', 'inventory.adjust',
            'attendance.view_all', 'attendance.exempt_penalty',
            'shifts.manage', 'shifts.approve_swap',
            'sales.view_analytics', 'reports.export',
            'cash_advance.approve', 'payroll.view', 'payroll.disburse',
            'roles.view', 'members.view',
        ];

        foreach ($gmFinancePermissions as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $gmFinanceRoleKaliurang->id,
                'permission' => $p,
            ]);
        }

        $seniorBaristaRoleKaliurang = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'name' => 'Senior Barista & Kasir',
            ],
            [
                'description' => 'Akses operasional kasir POS, inventory stock view & adjustment, dan monitoring jadwal shift.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        foreach (['catalog.view', 'inventory.view', 'inventory.adjust'] as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $seniorBaristaRoleKaliurang->id,
                'permission' => $p,
            ]);
        }

        $baristaRoleKaliurang = WorkspaceRole::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'name' => 'Barista & Kasir',
            ],
            [
                'description' => 'Akses operasional pesanan kasir POS dan inventaris bahan baku.',
                'is_system' => false,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        foreach (['catalog.view', 'inventory.view'] as $p) {
            WorkspaceRolePermission::firstOrCreate([
                'role_id' => $baristaRoleKaliurang->id,
                'permission' => $p,
            ]);
        }

        // WS 2 (Kaliurang) - Manager: Ajril Syahputra
        $managerKaliurang = User::firstOrCreate(
            ['email' => 'ajril@gmail.com'],
            [
                'name' => 'Ajril Syahputra',
                'password' => Hash::make('123456'),
                'bank_name' => 'BCA',
                'bank_account_number' => '8839201928',
                'bank_account_holder' => 'Ajril Syahputra',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $memberManagerKaliurang = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $managerKaliurang->id,
            ],
            [
                'branch_id' => $branchKaliurang->id,
                'job_title' => 'General Manager',
                'role' => 'MANAGER',
                'role_id' => $gmFinanceRoleKaliurang->id,
                'pin' => Hash::make('1234'),
                'base_salary' => 4500000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberManagerKaliurang->update(['role_id' => $gmFinanceRoleKaliurang->id]);

        // WS 2 (Kaliurang) - Staff 1: Rama
        $staffRama = User::firstOrCreate(
            ['email' => 'rama@gmail.com'],
            [
                'name' => 'Rama Aditya',
                'password' => Hash::make('123456'),
                'bank_name' => 'BCA',
                'bank_account_number' => '7720194821',
                'bank_account_holder' => 'Rama Aditya',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $memberRama = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $staffRama->id,
            ],
            [
                'branch_id' => $branchKaliurang->id,
                'job_title' => 'Senior Barista & Kasir',
                'role' => 'STAFF',
                'role_id' => $seniorBaristaRoleKaliurang->id,
                'pin' => Hash::make('3344'),
                'base_salary' => 2850000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberRama->update(['role_id' => $seniorBaristaRoleKaliurang->id]);

        // WS 2 (Kaliurang) - Staff 2: Kia
        $staffKia = User::firstOrCreate(
            ['email' => 'kia@gmail.com'],
            [
                'name' => 'Zaskia Putri',
                'password' => Hash::make('123456'),
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1370098765432',
                'bank_account_holder' => 'Zaskia Putri',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $memberKia = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $staffKia->id,
            ],
            [
                'branch_id' => $branchKaliurang->id,
                'job_title' => 'Barista & Kasir',
                'role' => 'STAFF',
                'role_id' => $baristaRoleKaliurang->id,
                'pin' => Hash::make('4455'),
                'base_salary' => 2750000.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );
        $memberKia->update(['role_id' => $baristaRoleKaliurang->id]);

        // Base Shift Assignments for Today (Unit & Feature Test requirement)
        $todayStr = $now->toDateString();
        ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'shift_template_id' => $shiftPagiSeturan->id,
                'assigned_user_id' => $staffAmi->id,
                'date' => $todayStr,
            ],
            [
                'actual_user_id' => $staffAmi->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'created_by_user_id' => $managerSeturan->id,
            ]
        );

        ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'shift_template_id' => $shiftPagiKaliurang->id,
                'assigned_user_id' => $staffRama->id,
                'date' => $todayStr,
            ],
            [
                'actual_user_id' => $staffRama->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'created_by_user_id' => $managerKaliurang->id,
            ]
        );

        if (app()->environment('testing') || app()->runningUnitTests()) {
            return;
        }

        // Mapping foto selfie 6 staff & managers
        $userPhotos = [
            $staffAmi->id => '/seeders/attendance/absen1.webp',
            $staffHani->id => '/seeders/attendance/absen2.webp',
            $staffRama->id => '/seeders/attendance/absen3.webp',
            $staffKia->id => '/seeders/attendance/absen4.webp',
            $managerSeturan->id => '/seeders/attendance/absen5.webp',
            $managerKaliurang->id => '/seeders/attendance/absen5.webp',
        ];

        // 3. Histori Kasbon (Cash Advances) 5 Tahun
        $cashAdvanceRows = [];
        $staffPool = [
            ['user' => $staffAmi, 'ws' => $workspaceSeturan, 'mgr' => $managerSeturan],
            ['user' => $staffHani, 'ws' => $workspaceSeturan, 'mgr' => $managerSeturan],
            ['user' => $staffRama, 'ws' => $workspaceKaliurang, 'mgr' => $managerKaliurang],
            ['user' => $staffKia, 'ws' => $workspaceKaliurang, 'mgr' => $managerKaliurang],
        ];

        // Kasbon lampau tiap 2-3 bulan selama 59 bulan ke belakang
        $kasbonDeductionMap = [];

        for ($m = 58; $m >= 2; $m -= 3) {
            $staffEntry = $staffPool[($m / 3) % count($staffPool)];
            $reqDate = (clone $now)->subMonths($m)->startOfMonth()->addDays(rand(5, 15))->toDateString();
            $deductDate = (clone $now)->subMonths($m)->endOfMonth()->toDateString();
            $amount = (float) (rand(2, 5) * 100000);

            $cashAdvanceRows[] = [
                'id' => (string) Str::uuid(),
                'workspace_id' => $staffEntry['ws']->id,
                'user_id' => $staffEntry['user']->id,
                'amount' => $amount,
                'request_date' => $reqDate,
                'status' => 'DEDUCTED',
                'approved_by_user_id' => $staffEntry['mgr']->id,
                'deducted_at_payroll_date' => $deductDate,
                'created_at' => $reqDate . ' 08:30:00',
                'updated_at' => $deductDate . ' 17:00:00',
            ];

            $kasbonDeductionMap["{$m}:{$staffEntry['user']->id}"] = $amount;
        }

        // Kasbon terkini
        $reqAmiLast = (clone $now)->subDays(45)->toDateString();
        $deductAmiLast = (clone $now)->subDays(30)->toDateString();
        $cashAdvanceRows[] = [
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceSeturan->id,
            'user_id' => $staffAmi->id,
            'amount' => 500000.00,
            'request_date' => $reqAmiLast,
            'status' => 'DEDUCTED',
            'approved_by_user_id' => $managerSeturan->id,
            'deducted_at_payroll_date' => $deductAmiLast,
            'created_at' => $reqAmiLast . ' 09:00:00',
            'updated_at' => $deductAmiLast . ' 17:00:00',
        ];
        $kasbonDeductionMap["1:{$staffAmi->id}"] = 500000.00;

        $reqRamaLast = (clone $now)->subDays(40)->toDateString();
        $deductRamaLast = (clone $now)->subDays(30)->toDateString();
        $cashAdvanceRows[] = [
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceKaliurang->id,
            'user_id' => $staffRama->id,
            'amount' => 400000.00,
            'request_date' => $reqRamaLast,
            'status' => 'DEDUCTED',
            'approved_by_user_id' => $managerKaliurang->id,
            'deducted_at_payroll_date' => $deductRamaLast,
            'created_at' => $reqRamaLast . ' 10:15:00',
            'updated_at' => $deductRamaLast . ' 17:00:00',
        ];
        $kasbonDeductionMap["1:{$staffRama->id}"] = 400000.00;

        $reqHani = (clone $now)->subDays(2)->toDateString();
        $cashAdvanceRows[] = [
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceSeturan->id,
            'user_id' => $staffHani->id,
            'amount' => 300000.00,
            'request_date' => $reqHani,
            'status' => 'PENDING',
            'approved_by_user_id' => null,
            'deducted_at_payroll_date' => null,
            'created_at' => $reqHani . ' 14:00:00',
            'updated_at' => $reqHani . ' 14:00:00',
        ];

        $reqKia = (clone $now)->subDay()->toDateString();
        $cashAdvanceRows[] = [
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceKaliurang->id,
            'user_id' => $staffKia->id,
            'amount' => 250000.00,
            'request_date' => $reqKia,
            'status' => 'PENDING',
            'approved_by_user_id' => null,
            'deducted_at_payroll_date' => null,
            'created_at' => $reqKia . ' 11:20:00',
            'updated_at' => $reqKia . ' 11:20:00',
        ];

        DB::table('cash_advances')->delete();
        foreach (array_chunk($cashAdvanceRows, 100) as $chunk) {
            DB::table('cash_advances')->insert($chunk);
        }

        // 4. Histori Jadwal Shift & Presensi 5 Tahun Ke Belakang
        $startDate = (clone $now)->subYears(5)->startOfMonth();
        $currentDate = clone $startDate;

        $shiftAssignmentRows = [];
        $attendanceRows = [];

        DB::table('attendances')->delete();
        DB::table('shift_assignments')->delete();

        $flushShiftAndAttendance = function () use (&$shiftAssignmentRows, &$attendanceRows): void {
            if (! empty($shiftAssignmentRows)) {
                DB::table('shift_assignments')->insert($shiftAssignmentRows);
                $shiftAssignmentRows = [];
            }
            if (! empty($attendanceRows)) {
                DB::table('attendances')->insert($attendanceRows);
                $attendanceRows = [];
            }
        };

        $swapApprovedDaySeturan = (clone $now)->subDays(20)->toDateString();
        $swapPendingDaySeturan = (clone $now)->addDay()->toDateString();
        $swapApprovedDayKaliurang = (clone $now)->subDays(15)->toDateString();
        $swapPendingDayKaliurang = (clone $now)->addDays(2)->toDateString();

        DB::beginTransaction();

        try {
            while ($currentDate->lte($now->copy()->addDays(7))) {
                $dateStr = $currentDate->toDateString();
                $isPast = $currentDate->lt($now->copy()->startOfDay());
                $isToday = $currentDate->isSameDay($now);
                $dayIndex = (int) $currentDate->format('j');

                // --- CABANG 1: SETURAN (WS 1) ---
                $isAmiPagi = ($dayIndex % 2 === 0);
                $templateAmi = $isAmiPagi ? $shiftPagiSeturan : $shiftSoreSeturan;
                $templateHani = $isAmiPagi ? $shiftSoreSeturan : $shiftPagiSeturan;

                $isSwapAmiApproved = ($dateStr === $swapApprovedDaySeturan);
                $isSwapHaniPending = ($dateStr === $swapPendingDaySeturan);

                $assignAmiId = (string) Str::uuid();
                $shiftAssignmentRows[] = [
                    'id' => $assignAmiId,
                    'workspace_id' => $workspaceSeturan->id,
                    'shift_template_id' => $templateAmi->id,
                    'assigned_user_id' => $staffAmi->id,
                    'actual_user_id' => $isSwapAmiApproved ? $staffHani->id : $staffAmi->id,
                    'date' => $dateStr,
                    'is_swap' => $isSwapAmiApproved,
                    'swap_status' => $isSwapAmiApproved ? 'APPROVED' : 'NONE',
                    'swap_approved_by_user_id' => $isSwapAmiApproved ? $managerSeturan->id : null,
                    'created_by_user_id' => $managerSeturan->id,
                    'created_at' => $dateStr . ' 06:00:00',
                    'updated_at' => $dateStr . ' 06:00:00',
                ];

                $assignHaniId = (string) Str::uuid();
                $shiftAssignmentRows[] = [
                    'id' => $assignHaniId,
                    'workspace_id' => $workspaceSeturan->id,
                    'shift_template_id' => $templateHani->id,
                    'assigned_user_id' => $staffHani->id,
                    'actual_user_id' => $staffHani->id,
                    'date' => $dateStr,
                    'is_swap' => $isSwapHaniPending,
                    'swap_status' => $isSwapHaniPending ? 'PENDING' : 'NONE',
                    'swap_approved_by_user_id' => null,
                    'created_by_user_id' => $managerSeturan->id,
                    'created_at' => $dateStr . ' 06:00:00',
                    'updated_at' => $dateStr . ' 06:00:00',
                ];

                if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                    $assignMgrSeturanId = (string) Str::uuid();
                    $shiftAssignmentRows[] = [
                        'id' => $assignMgrSeturanId,
                        'workspace_id' => $workspaceSeturan->id,
                        'shift_template_id' => $shiftPagiSeturan->id,
                        'assigned_user_id' => $managerSeturan->id,
                        'actual_user_id' => $managerSeturan->id,
                        'date' => $dateStr,
                        'is_swap' => false,
                        'swap_status' => 'NONE',
                        'swap_approved_by_user_id' => null,
                        'created_by_user_id' => $workspaceSeturan->owner_user_id,
                        'created_at' => $dateStr . ' 06:00:00',
                        'updated_at' => $dateStr . ' 06:00:00',
                    ];
                }

                // --- CABANG 2: KALIURANG (WS 2) ---
                $isRamaPagi = ($dayIndex % 2 === 0);
                $templateRama = $isRamaPagi ? $shiftPagiKaliurang : $shiftSoreKaliurang;
                $templateKia = $isRamaPagi ? $shiftSoreKaliurang : $shiftPagiKaliurang;

                $isSwapRamaApproved = ($dateStr === $swapApprovedDayKaliurang);
                $isSwapKiaPending = ($dateStr === $swapPendingDayKaliurang);

                $assignRamaId = (string) Str::uuid();
                $shiftAssignmentRows[] = [
                    'id' => $assignRamaId,
                    'workspace_id' => $workspaceKaliurang->id,
                    'shift_template_id' => $templateRama->id,
                    'assigned_user_id' => $staffRama->id,
                    'actual_user_id' => $isSwapRamaApproved ? $staffKia->id : $staffRama->id,
                    'date' => $dateStr,
                    'is_swap' => $isSwapRamaApproved,
                    'swap_status' => $isSwapRamaApproved ? 'APPROVED' : 'NONE',
                    'swap_approved_by_user_id' => $isSwapRamaApproved ? $managerKaliurang->id : null,
                    'created_by_user_id' => $managerKaliurang->id,
                    'created_at' => $dateStr . ' 06:00:00',
                    'updated_at' => $dateStr . ' 06:00:00',
                ];

                $assignKiaId = (string) Str::uuid();
                $shiftAssignmentRows[] = [
                    'id' => $assignKiaId,
                    'workspace_id' => $workspaceKaliurang->id,
                    'shift_template_id' => $templateKia->id,
                    'assigned_user_id' => $staffKia->id,
                    'actual_user_id' => $staffKia->id,
                    'date' => $dateStr,
                    'is_swap' => $isSwapKiaPending,
                    'swap_status' => $isSwapKiaPending ? 'PENDING' : 'NONE',
                    'swap_approved_by_user_id' => null,
                    'created_by_user_id' => $managerKaliurang->id,
                    'created_at' => $dateStr . ' 06:00:00',
                    'updated_at' => $dateStr . ' 06:00:00',
                ];

                if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                    $assignMgrKaliurangId = (string) Str::uuid();
                    $shiftAssignmentRows[] = [
                        'id' => $assignMgrKaliurangId,
                        'workspace_id' => $workspaceKaliurang->id,
                        'shift_template_id' => $shiftPagiKaliurang->id,
                        'assigned_user_id' => $managerKaliurang->id,
                        'actual_user_id' => $managerKaliurang->id,
                        'date' => $dateStr,
                        'is_swap' => false,
                        'swap_status' => 'NONE',
                        'swap_approved_by_user_id' => null,
                        'created_by_user_id' => $workspaceKaliurang->owner_user_id,
                        'created_at' => $dateStr . ' 06:00:00',
                        'updated_at' => $dateStr . ' 06:00:00',
                    ];
                }

                // Presensi untuk hari lampau & hari ini
                if ($isPast || $isToday) {
                    $staffDuty = [
                        ['user' => $staffAmi, 'assign_id' => $assignAmiId, 'tpl' => $templateAmi, 'branch' => $branchSeturan, 'ws' => $workspaceSeturan],
                        ['user' => $staffHani, 'assign_id' => $assignHaniId, 'tpl' => $templateHani, 'branch' => $branchSeturan, 'ws' => $workspaceSeturan],
                        ['user' => $staffRama, 'assign_id' => $assignRamaId, 'tpl' => $templateRama, 'branch' => $branchKaliurang, 'ws' => $workspaceKaliurang],
                        ['user' => $staffKia, 'assign_id' => $assignKiaId, 'tpl' => $templateKia, 'branch' => $branchKaliurang, 'ws' => $workspaceKaliurang],
                    ];

                    foreach ($staffDuty as $duty) {
                        $u = $duty['user'];
                        $asId = $duty['assign_id'];
                        $tpl = $duty['tpl'];
                        $br = $duty['branch'];
                        $ws = $duty['ws'];

                        $inExpected = Carbon::parse($dateStr . ' ' . $tpl->expected_clock_in);
                        $outExpected = Carbon::parse($dateStr . ' ' . $tpl->expected_clock_out);
                        if ($outExpected->lte($inExpected)) {
                            $outExpected->addDay();
                        }

                        $isLate = (rand(1, 100) <= 15);
                        $lateMin = $isLate ? rand(5, 20) : 0;
                        $actualIn = (clone $inExpected)->addMinutes($lateMin)->subMinutes($isLate ? 0 : rand(2, 10));

                        $hasOvertime = $currentDate->isWeekend() && (rand(1, 100) <= 25);
                        $overtimeMin = $hasOvertime ? (rand(1, 2) * 60) : 0;
                        $actualOut = (clone $outExpected)->addMinutes($overtimeMin);

                        $isClockOutDone = $isPast;

                        $attendanceRows[] = [
                            'id' => (string) Str::uuid(),
                            'workspace_id' => $ws->id,
                            'user_id' => $u->id,
                            'branch_id' => $br->id,
                            'shift_assignment_id' => $asId,
                            'clock_in_time' => $actualIn->toDateTimeString(),
                            'clock_out_time' => $isClockOutDone ? $actualOut->toDateTimeString() : null,
                            'photo_in_url' => $userPhotos[$u->id],
                            'photo_out_url' => $isClockOutDone ? $userPhotos[$u->id] : null,
                            'lat_in' => $br->lat + (rand(-50, 50) / 1000000),
                            'lng_in' => $br->lng + (rand(-50, 50) / 1000000),
                            'lat_out' => $isClockOutDone ? ($br->lat + (rand(-50, 50) / 1000000)) : null,
                            'lng_out' => $isClockOutDone ? ($br->lng + (rand(-50, 50) / 1000000)) : null,
                            'status' => 'APPROVED',
                            'late_minutes' => $lateMin,
                            'overtime_minutes' => $overtimeMin,
                            'is_manual_override' => false,
                            'notes' => $isLate ? 'Keterlambatan lalu lintas' : null,
                            'created_at' => $actualIn->toDateTimeString(),
                            'updated_at' => ($isClockOutDone ? $actualOut : $actualIn)->toDateTimeString(),
                        ];
                    }
                }

                if (count($shiftAssignmentRows) >= 500) {
                    $flushShiftAndAttendance();
                }

                $currentDate->addDay();
            }

            $flushShiftAndAttendance();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // 5. Histori Payroll 5 Tahun Ke Belakang (59 Bulan Lampau yang sudah DISBURSED)
        $membersSeturan = [$memberManagerSeturan, $memberAmi, $memberHani];
        $membersKaliurang = [$memberManagerKaliurang, $memberRama, $memberKia];

        $payrollRows = [];

        for ($i = 59; $i >= 1; $i--) {
            $periodStart = (clone $now)->subMonths($i)->startOfMonth()->toDateString();
            $periodEnd = (clone $now)->subMonths($i)->endOfMonth()->toDateString();
            $disbursedAt = (clone $now)->subMonths($i)->endOfMonth()->setHour(17)->setMinute(0)->toDateTimeString();

            foreach ($membersSeturan as $m) {
                $salary = (float) $m->base_salary;
                $kasbonDeduction = (float) ($kasbonDeductionMap["{$i}:{$m->user_id}"] ?? 0.00);
                $overtime = rand(1, 3) * 20000.00;
                $latePenalty = (rand(1, 10) > 7) ? 15000.00 : 0.00;
                $netSalary = max(0.00, $salary + $overtime - $latePenalty - $kasbonDeduction);

                $payrollRows[] = [
                    'id' => (string) Str::uuid(),
                    'workspace_id' => $workspaceSeturan->id,
                    'workspace_member_id' => $m->id,
                    'user_id' => $m->user_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'base_salary' => $salary,
                    'overtime_pay' => $overtime,
                    'late_penalty' => $latePenalty,
                    'cash_advance_deduction' => $kasbonDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $disbursedAt,
                    'created_at' => $disbursedAt,
                    'updated_at' => $disbursedAt,
                ];
            }

            foreach ($membersKaliurang as $m) {
                $salary = (float) $m->base_salary;
                $kasbonDeduction = (float) ($kasbonDeductionMap["{$i}:{$m->user_id}"] ?? 0.00);
                $overtime = rand(1, 3) * 20000.00;
                $latePenalty = (rand(1, 10) > 7) ? 15000.00 : 0.00;
                $netSalary = max(0.00, $salary + $overtime - $latePenalty - $kasbonDeduction);

                $payrollRows[] = [
                    'id' => (string) Str::uuid(),
                    'workspace_id' => $workspaceKaliurang->id,
                    'workspace_member_id' => $m->id,
                    'user_id' => $m->user_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'base_salary' => $salary,
                    'overtime_pay' => $overtime,
                    'late_penalty' => $latePenalty,
                    'cash_advance_deduction' => $kasbonDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $disbursedAt,
                    'created_at' => $disbursedAt,
                    'updated_at' => $disbursedAt,
                ];
            }
        }

        DB::table('payrolls')->delete();
        if (! empty($payrollRows)) {
            foreach (array_chunk($payrollRows, 250) as $chunk) {
                DB::table('payrolls')->insert($chunk);
            }
        }
    }
}
