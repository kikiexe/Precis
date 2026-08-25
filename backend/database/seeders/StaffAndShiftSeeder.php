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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffAndShiftSeeder extends Seeder
{
    public function run(): void
    {
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
            ]
        );

        // Shift Templates Kaliurang (pada Workspace 1)
        $shiftPagiKaliurang = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchKaliurang->id,
                'name' => 'Shift Pagi (Kaliurang)',
            ],
            [
                'expected_clock_in' => '08:00:00',
                'expected_clock_out' => '16:00:00',
            ]
        );

        $shiftSoreKaliurang = ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchKaliurang->id,
                'name' => 'Shift Sore (Kaliurang)',
            ],
            [
                'expected_clock_in' => '16:00:00',
                'expected_clock_out' => '00:00:00',
            ]
        );

        if ($workspaceKaliurang->id !== $workspaceSeturan->id) {
            ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'branch_id' => $branchKaliurang->id,
                    'name' => 'Shift Pagi (Kaliurang)',
                ],
                [
                    'expected_clock_in' => '08:00:00',
                    'expected_clock_out' => '16:00:00',
                ]
            );

            ShiftTemplate::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'branch_id' => $branchKaliurang->id,
                    'name' => 'Shift Sore (Kaliurang)',
                ],
                [
                    'expected_clock_in' => '16:00:00',
                    'expected_clock_out' => '00:00:00',
                ]
            );
        }

        // 2. Staff Users & Memberships
        // Manager: Paundra Mahendra (WS 1 Seturan)
        $manager = User::firstOrCreate(
            ['email' => 'paundra@gmail.com'],
            [
                'name' => 'Paundra Mahendra',
                'password' => Hash::make('123456'),
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1370019283741',
                'bank_account_holder' => 'Paundra Mahendra',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        $memberManager = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $manager->id,
            ],
            [
                'branch_id' => $branchSeturan->id,
                'job_title' => 'Store Manager',
                'role' => 'MANAGER',
                'pin' => Hash::make('1234'),
                'base_salary' => 4500000.00,
                'is_active' => true,
            ]
        );

        // Staff 1 Cabang 1: Ami (WS 1 Seturan)
        $staffAmi = User::firstOrCreate(
            ['email' => 'ami@gmail.com'],
            [
                'name' => 'Siti Ami Rahmawati',
                'password' => Hash::make('123456'),
                'bank_name' => 'BRI',
                'bank_account_number' => '012301082736501',
                'bank_account_holder' => 'Siti Ami Rahmawati',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => now(),
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
                'pin' => Hash::make('1122'),
                'base_salary' => 2800000.00,
                'is_active' => true,
            ]
        );

        // Staff 2 Cabang 1: Hani (WS 1 Seturan)
        $staffHani = User::firstOrCreate(
            ['email' => 'hani@gmail.com'],
            [
                'name' => 'Hani Handayani',
                'password' => Hash::make('123456'),
                'bank_name' => 'BNI',
                'bank_account_number' => '0987654321',
                'bank_account_holder' => 'Hani Handayani',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => now(),
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
                'pin' => Hash::make('2233'),
                'base_salary' => 2700000.00,
                'is_active' => true,
            ]
        );

        // Staff 3 Cabang 2: Rama (WS 2 Kaliurang)
        // Staff 3 Cabang 2: Rama (Kaliurang)
        $staffRama = User::firstOrCreate(
            ['email' => 'rama@gmail.com'],
            [
                'name' => 'Rama Aditya',
                'password' => Hash::make('123456'),
                'bank_name' => 'BCA',
                'bank_account_number' => '7720194821',
                'bank_account_holder' => 'Rama Aditya',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        $memberRama = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffRama->id,
            ],
            [
                'branch_id' => $branchKaliurang->id,
                'job_title' => 'Senior Barista & Kasir',
                'role' => 'STAFF',
                'pin' => Hash::make('3344'),
                'base_salary' => 2850000.00,
                'is_active' => true,
            ]
        );

        if ($workspaceKaliurang->id !== $workspaceSeturan->id) {
            WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'user_id' => $staffRama->id,
                ],
                [
                    'branch_id' => $branchKaliurang->id,
                    'job_title' => 'Senior Barista & Kasir',
                    'role' => 'STAFF',
                    'pin' => Hash::make('3344'),
                    'base_salary' => 2850000.00,
                    'is_active' => true,
                ]
            );
        }

        // Staff 4 Cabang 2: Kia (Kaliurang)
        $staffKia = User::firstOrCreate(
            ['email' => 'kia@gmail.com'],
            [
                'name' => 'Zaskia Putri',
                'password' => Hash::make('123456'),
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1370098765432',
                'bank_account_holder' => 'Zaskia Putri',
                'subscription_status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        $memberKia = WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffKia->id,
            ],
            [
                'branch_id' => $branchKaliurang->id,
                'job_title' => 'Barista & Kasir',
                'role' => 'STAFF',
                'pin' => Hash::make('4455'),
                'base_salary' => 2750000.00,
                'is_active' => true,
            ]
        );

        if ($workspaceKaliurang->id !== $workspaceSeturan->id) {
            WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'user_id' => $staffKia->id,
                ],
                [
                    'branch_id' => $branchKaliurang->id,
                    'job_title' => 'Barista & Kasir',
                    'role' => 'STAFF',
                    'pin' => Hash::make('4455'),
                    'base_salary' => 2750000.00,
                    'is_active' => true,
                ]
            );
        }

        // Base Shift Assignments for Today (Testing & Base)
        $todayStr = now()->toDateString();
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
                'created_by_user_id' => $manager->id,
            ]
        );

        ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'shift_template_id' => $shiftPagiKaliurang->id,
                'assigned_user_id' => $staffRama->id,
                'date' => $todayStr,
            ],
            [
                'actual_user_id' => $staffRama->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'created_by_user_id' => $manager->id,
            ]
        );

        if ($workspaceKaliurang->id !== $workspaceSeturan->id) {
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
                    'created_by_user_id' => $workspaceKaliurang->owner_user_id,
                ]
            );
        }

        // Jika dalam unit/feature test runner, cukup seed baseline data di atas
        if (app()->environment('testing') || app()->runningUnitTests()) {
            return;
        }

        // Mapping foto selfie 4 staff + manager
        $userPhotos = [
            $staffAmi->id => '/seeders/attendance/absen1.webp',
            $staffHani->id => '/seeders/attendance/absen2.webp',
            $staffRama->id => '/seeders/attendance/absen3.webp',
            $staffKia->id => '/seeders/attendance/absen4.webp',
            $manager->id => '/seeders/attendance/absen5.webp',
        ];

        // 3. Histori Kasbon (Cash Advances)
        // Kasbon Ami (WS 1 Seturan)
        CashAdvance::firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffAmi->id,
                'amount' => 500000.00,
                'request_date' => now()->subDays(45)->toDateString(),
            ],
            [
                'status' => 'DEDUCTED',
                'approved_by_user_id' => $manager->id,
                'deducted_at_payroll_date' => now()->subDays(30)->toDateString(),
            ]
        );

        // Kasbon Hani (WS 1 Seturan - Pending)
        CashAdvance::firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $staffHani->id,
                'amount' => 300000.00,
                'request_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'status' => 'PENDING',
                'approved_by_user_id' => null,
            ]
        );

        // Kasbon Rama (WS 2 Kaliurang)
        CashAdvance::firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $staffRama->id,
                'amount' => 400000.00,
                'request_date' => now()->subDays(60)->toDateString(),
            ],
            [
                'status' => 'DEDUCTED',
                'approved_by_user_id' => $workspaceKaliurang->owner_user_id,
                'deducted_at_payroll_date' => now()->subDays(30)->toDateString(),
            ]
        );

        // Kasbon Kia (WS 2 Kaliurang - Pending)
        CashAdvance::firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $staffKia->id,
                'amount' => 250000.00,
                'request_date' => now()->subDay()->toDateString(),
            ],
            [
                'status' => 'PENDING',
                'approved_by_user_id' => null,
            ]
        );

        // 4. Histori Jadwal Shift & Presensi 3 Bulan Ke Belakang
        $now = Carbon::now();
        $startDate = (clone $now)->subMonths(3)->startOfDay();
        $currentDate = clone $startDate;

        $attendanceRows = [];
        $swapApprovedDaySeturan = (clone $now)->subDays(20)->toDateString();
        $swapPendingDaySeturan = (clone $now)->addDay()->toDateString();
        $swapApprovedDayKaliurang = (clone $now)->subDays(15)->toDateString();
        $swapPendingDayKaliurang = (clone $now)->addDays(2)->toDateString();

        while ($currentDate->lte($now->copy()->addDays(7))) {
            $dateStr = $currentDate->toDateString();
            $isPast = $currentDate->lt($now->copy()->startOfDay());
            $isToday = $currentDate->isSameDay($now);
            $dayIndex = (int) $currentDate->format('j');

            // --- CABANG 1: SETURAN (WS 1) ---
            $isAmiPagi = ($dayIndex % 2 === 0);
            $templateAmi = $isAmiPagi ? $shiftPagiSeturan : $shiftSoreSeturan;
            $templateHani = $isAmiPagi ? $shiftSoreSeturan : $shiftPagiSeturan;

            // Shift Assignment Ami
            $isSwapAmiApproved = ($dateStr === $swapApprovedDaySeturan);
            $isSwapHaniPending = ($dateStr === $swapPendingDaySeturan);

            $assignAmi = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceSeturan->id,
                    'shift_template_id' => $templateAmi->id,
                    'assigned_user_id' => $staffAmi->id,
                    'date' => $dateStr,
                ],
                [
                    'actual_user_id' => $isSwapAmiApproved ? $staffHani->id : $staffAmi->id,
                    'is_swap' => $isSwapAmiApproved,
                    'swap_status' => $isSwapAmiApproved ? 'APPROVED' : 'NONE',
                    'swap_approved_by_user_id' => $isSwapAmiApproved ? $manager->id : null,
                    'created_by_user_id' => $manager->id,
                ]
            );

            // Shift Assignment Hani
            $assignHani = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceSeturan->id,
                    'shift_template_id' => $templateHani->id,
                    'assigned_user_id' => $staffHani->id,
                    'date' => $dateStr,
                ],
                [
                    'actual_user_id' => $staffHani->id,
                    'is_swap' => $isSwapHaniPending,
                    'swap_status' => $isSwapHaniPending ? 'PENDING' : 'NONE',
                    'created_by_user_id' => $manager->id,
                ]
            );

            // Shift Assignment Manager Paundra (Senin-Sabtu)
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $workspaceSeturan->id,
                        'shift_template_id' => $shiftPagiSeturan->id,
                        'assigned_user_id' => $manager->id,
                        'date' => $dateStr,
                    ],
                    [
                        'actual_user_id' => $manager->id,
                        'is_swap' => false,
                        'swap_status' => 'NONE',
                        'created_by_user_id' => $workspaceSeturan->owner_user_id,
                    ]
                );
            }

            // --- CABANG 2: KALIURANG (WS 2) ---
            $isRamaPagi = ($dayIndex % 2 === 0);
            $templateRama = $isRamaPagi ? $shiftPagiKaliurang : $shiftSoreKaliurang;
            $templateKia = $isRamaPagi ? $shiftSoreKaliurang : $shiftPagiKaliurang;

            $isSwapRamaApproved = ($dateStr === $swapApprovedDayKaliurang);
            $isSwapKiaPending = ($dateStr === $swapPendingDayKaliurang);

            $assignRama = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'shift_template_id' => $templateRama->id,
                    'assigned_user_id' => $staffRama->id,
                    'date' => $dateStr,
                ],
                [
                    'actual_user_id' => $isSwapRamaApproved ? $staffKia->id : $staffRama->id,
                    'is_swap' => $isSwapRamaApproved,
                    'swap_status' => $isSwapRamaApproved ? 'APPROVED' : 'NONE',
                    'swap_approved_by_user_id' => $isSwapRamaApproved ? $workspaceKaliurang->owner_user_id : null,
                    'created_by_user_id' => $workspaceKaliurang->owner_user_id,
                ]
            );

            $assignKia = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'shift_template_id' => $templateKia->id,
                    'assigned_user_id' => $staffKia->id,
                    'date' => $dateStr,
                ],
                [
                    'actual_user_id' => $staffKia->id,
                    'is_swap' => $isSwapKiaPending,
                    'swap_status' => $isSwapKiaPending ? 'PENDING' : 'NONE',
                    'created_by_user_id' => $workspaceKaliurang->owner_user_id,
                ]
            );

            // Presensi untuk hari-hari lampau & hari ini
            if ($isPast || $isToday) {
                $staffDuty = [
                    ['user' => $staffAmi, 'assign' => $assignAmi, 'tpl' => $templateAmi, 'branch' => $branchSeturan, 'ws' => $workspaceSeturan],
                    ['user' => $staffHani, 'assign' => $assignHani, 'tpl' => $templateHani, 'branch' => $branchSeturan, 'ws' => $workspaceSeturan],
                    ['user' => $staffRama, 'assign' => $assignRama, 'tpl' => $templateRama, 'branch' => $branchKaliurang, 'ws' => $workspaceKaliurang],
                    ['user' => $staffKia, 'assign' => $assignKia, 'tpl' => $templateKia, 'branch' => $branchKaliurang, 'ws' => $workspaceKaliurang],
                ];

                foreach ($staffDuty as $duty) {
                    $u = $duty['user'];
                    $as = $duty['assign'];
                    $tpl = $duty['tpl'];
                    $br = $duty['branch'];
                    $ws = $duty['ws'];

                    $inExpected = Carbon::parse($dateStr . ' ' . $tpl->expected_clock_in);
                    $outExpected = Carbon::parse($dateStr . ' ' . $tpl->expected_clock_out);
                    if ($outExpected->lte($inExpected)) {
                        $outExpected->addDay();
                    }

                    // Variasi kedisiplinan: 85% tepat waktu, 15% telat 5-20 menit
                    $isLate = (rand(1, 100) <= 15);
                    $lateMin = $isLate ? rand(5, 20) : 0;
                    $actualIn = (clone $inExpected)->addMinutes($lateMin)->subMinutes($isLate ? 0 : rand(2, 10));

                    // Overtime di weekend
                    $hasOvertime = $currentDate->isWeekend() && (rand(1, 100) <= 25);
                    $overtimeMin = $hasOvertime ? (rand(1, 2) * 60) : 0;
                    $actualOut = (clone $outExpected)->addMinutes($overtimeMin);

                    $isClockOutDone = $isPast;

                    $attendanceRows[] = [
                        'id' => (string) Str::uuid(),
                        'workspace_id' => $ws->id,
                        'user_id' => $u->id,
                        'branch_id' => $br->id,
                        'shift_assignment_id' => $as->id,
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
                        'created_at' => $actualIn,
                        'updated_at' => $isClockOutDone ? $actualOut : $actualIn,
                    ];
                }
            }

            $currentDate->addDay();
        }

        if (! empty($attendanceRows)) {
            // Chunk insert
            foreach (array_chunk($attendanceRows, 100) as $chunk) {
                DB::table('attendances')->insert($chunk);
            }
        }

        // 5. Histori Payroll 2 Bulan Lalu (M-2 dan M-1) yang sudah dicairkan (DISBURSED)
        $membersSeturan = [$memberManager, $memberAmi, $memberHani];
        $membersKaliurang = [$memberRama, $memberKia];

        // Payroll M-2 (2 Bulan Lalu)
        $m2Start = (clone $now)->subMonths(2)->startOfMonth()->toDateString();
        $m2End = (clone $now)->subMonths(2)->endOfMonth()->toDateString();
        $m2DisbursedAt = (clone $now)->subMonths(2)->endOfMonth()->setHour(17)->setMinute(0)->toDateTimeString();

        foreach ($membersSeturan as $m) {
            $salary = (float) $m->base_salary;
            Payroll::firstOrCreate(
                [
                    'workspace_id' => $workspaceSeturan->id,
                    'workspace_member_id' => $m->id,
                    'period_start' => $m2Start,
                    'period_end' => $m2End,
                ],
                [
                    'user_id' => $m->user_id,
                    'base_salary' => $salary,
                    'overtime_pay' => 60000.00,
                    'late_penalty' => 15000.00,
                    'cash_advance_deduction' => 0.00,
                    'net_salary' => $salary + 60000.00 - 15000.00,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $m2DisbursedAt,
                ]
            );
        }

        foreach ($membersKaliurang as $m) {
            $salary = (float) $m->base_salary;
            Payroll::firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'workspace_member_id' => $m->id,
                    'period_start' => $m2Start,
                    'period_end' => $m2End,
                ],
                [
                    'user_id' => $m->user_id,
                    'base_salary' => $salary,
                    'overtime_pay' => 60000.00,
                    'late_penalty' => 15000.00,
                    'cash_advance_deduction' => 0.00,
                    'net_salary' => $salary + 60000.00 - 15000.00,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $m2DisbursedAt,
                ]
            );
        }

        // Payroll M-1 (Bulan Lalu - dengan potongan kasbon Ami & Rama)
        $m1Start = (clone $now)->subMonth()->startOfMonth()->toDateString();
        $m1End = (clone $now)->subMonth()->endOfMonth()->toDateString();
        $m1DisbursedAt = (clone $now)->subMonth()->endOfMonth()->setHour(17)->setMinute(0)->toDateTimeString();

        foreach ($membersSeturan as $m) {
            $salary = (float) $m->base_salary;
            $kasbonDeduction = ($m->user_id === $staffAmi->id) ? 500000.00 : 0.00;

            Payroll::firstOrCreate(
                [
                    'workspace_id' => $workspaceSeturan->id,
                    'workspace_member_id' => $m->id,
                    'period_start' => $m1Start,
                    'period_end' => $m1End,
                ],
                [
                    'user_id' => $m->user_id,
                    'base_salary' => $salary,
                    'overtime_pay' => 40000.00,
                    'late_penalty' => 10000.00,
                    'cash_advance_deduction' => $kasbonDeduction,
                    'net_salary' => $salary + 40000.00 - 10000.00 - $kasbonDeduction,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $m1DisbursedAt,
                ]
            );
        }

        foreach ($membersKaliurang as $m) {
            $salary = (float) $m->base_salary;
            $kasbonDeduction = ($m->user_id === $staffRama->id) ? 400000.00 : 0.00;

            Payroll::firstOrCreate(
                [
                    'workspace_id' => $workspaceKaliurang->id,
                    'workspace_member_id' => $m->id,
                    'period_start' => $m1Start,
                    'period_end' => $m1End,
                ],
                [
                    'user_id' => $m->user_id,
                    'base_salary' => $salary,
                    'overtime_pay' => 40000.00,
                    'late_penalty' => 10000.00,
                    'cash_advance_deduction' => $kasbonDeduction,
                    'net_salary' => $salary + 40000.00 - 10000.00 - $kasbonDeduction,
                    'status' => 'DISBURSED',
                    'disbursed_at' => $m1DisbursedAt,
                ]
            );
        }
    }
}
