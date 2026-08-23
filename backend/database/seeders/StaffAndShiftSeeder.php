<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CashAdvance;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffAndShiftSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();
        $branchSleman = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->firstOrFail();
        $branchMalioboro = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->where('name', 'like', '%Malioboro%')->firstOrFail();

        // 1. Salin 5 file foto absen dari folder assets seeder ke public storage backend jika belum ada
        $storageDir = storage_path('app/public/seeders/attendance');
        if (! File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        $photoFiles = ['absen1.webp', 'absen2.webp', 'absen3.webp', 'absen4.webp', 'absen5.webp'];
        $assetsDir = database_path('seeders/assets/attendance');

        foreach ($photoFiles as $photoName) {
            $sourceFile = $assetsDir . '/' . $photoName;
            $destFile = $storageDir . '/' . $photoName;
            if (File::exists($sourceFile) && ! File::exists($destFile)) {
                File::copy($sourceFile, $destFile);
            }
        }

        // 2. Shift Templates untuk Sleman & Malioboro
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

        // 3. Buat 5 Staf Outlet dengan kredensial & avatar foto
        $staffConfigs = [
            [
                'email' => 'budi.manager@amorecoffee.id',
                'name' => 'Budi Santoso (Store Manager)',
                'password' => 'BudiManager2026!',
                'role' => 'ADMIN',
                'branch_id' => $branchSleman->id,
                'pin' => '1234',
                'base_salary' => 4500000.00,
                'photo_num' => 1,
                'shift_template' => $shiftPagiSleman,
                'clock_in_time' => '07:00:00',
                'status' => 'ON_TIME',
                'late_minutes' => 0,
                'lat' => -7.71234,
                'lng' => 110.35467,
            ],
            [
                'email' => 'siti.kasir@amorecoffee.id',
                'name' => 'Siti Rahma (Barista/Kasir)',
                'password' => 'SitiKasir2026!',
                'role' => 'STAFF',
                'branch_id' => $branchSleman->id,
                'pin' => '1122',
                'base_salary' => 2800000.00,
                'photo_num' => 2,
                'shift_template' => $shiftPagiSleman,
                'clock_in_time' => '06:56:00',
                'status' => 'ON_TIME',
                'late_minutes' => 0,
                'lat' => -7.71235,
                'lng' => 110.35468,
            ],
            [
                'email' => 'anisa.barista@amorecoffee.id',
                'name' => 'Anisa Putri (Barista Sleman)',
                'password' => 'AnisaBarista2026!',
                'role' => 'STAFF',
                'branch_id' => $branchSleman->id,
                'pin' => '5566',
                'base_salary' => 2800000.00,
                'photo_num' => 3,
                'shift_template' => $shiftPagiSleman,
                'clock_in_time' => '07:08:00',
                'status' => 'LATE',
                'late_minutes' => 8,
                'lat' => -7.71233,
                'lng' => 110.35466,
            ],
            [
                'email' => 'dimas.kasir@amorecoffee.id',
                'name' => 'Dimas Pratama (Barista/Kasir)',
                'password' => 'DimasKasir2026!',
                'role' => 'STAFF',
                'branch_id' => $branchMalioboro->id,
                'pin' => '3344',
                'base_salary' => 2800000.00,
                'photo_num' => 4,
                'shift_template' => $shiftPagiMalioboro,
                'clock_in_time' => '07:55:00',
                'status' => 'ON_TIME',
                'late_minutes' => 0,
                'lat' => -7.79256,
                'lng' => 110.36589,
            ],
            [
                'email' => 'reza.kitchen@amorecoffee.id',
                'name' => 'Reza Kurniawan (Kitchen Staff)',
                'password' => 'RezaKitchen2026!',
                'role' => 'STAFF',
                'branch_id' => $branchMalioboro->id,
                'pin' => '7788',
                'base_salary' => 2700000.00,
                'photo_num' => 5,
                'shift_template' => $shiftPagiMalioboro,
                'clock_in_time' => '08:05:00',
                'status' => 'LATE',
                'late_minutes' => 5,
                'lat' => -7.79255,
                'lng' => 110.36590,
            ],
        ];

        $today = Carbon::today();
        $createdUsers = [];

        foreach ($staffConfigs as $cfg) {
            $user = User::firstOrCreate(
                ['email' => $cfg['email']],
                [
                    'name' => $cfg['name'],
                    'password' => Hash::make($cfg['password']),
                    'subscription_status' => 'ACTIVE',
                ]
            );
            $createdUsers[$cfg['email']] = $user;

            WorkspaceMember::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
                ],
                [
                    'branch_id' => $cfg['branch_id'],
                    'role' => $cfg['role'],
                    'pin' => Hash::make($cfg['pin']),
                    'base_salary' => $cfg['base_salary'],
                    'is_active' => true,
                ]
            );

            // 4. Buat Shift Assignment hari ini
            $assignment = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'shift_template_id' => $cfg['shift_template']->id,
                    'assigned_user_id' => $user->id,
                    'date' => $today->toDateString(),
                ],
                [
                    'actual_user_id' => $user->id,
                    'is_swap' => false,
                    'swap_status' => 'NONE',
                    'created_by_user_id' => $createdUsers['budi.manager@amorecoffee.id']->id ?? $user->id,
                ]
            );

            // 5. Buat Catatan Presensi Hari Ini (dengan Foto WebP lokal jika tersedia, atau avatar fallback)
            $destFile = $storageDir . "/absen{$cfg['photo_num']}.webp";
            if (File::exists($destFile)) {
                $photoUrl = url("/storage/seeders/attendance/absen{$cfg['photo_num']}.webp");
            } else {
                $encodedName = urlencode($cfg['name']);
                $photoUrl = "https://ui-avatars.com/api/?name={$encodedName}&background=17171c&color=fff&size=512";
            }
            $clockInDatetime = Carbon::parse($today->toDateString() . ' ' . $cfg['clock_in_time']);

            Attendance::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
                    'shift_assignment_id' => $assignment->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'branch_id' => $cfg['branch_id'],
                    'clock_in_time' => $clockInDatetime,
                    'clock_out_time' => null,
                    'photo_in_url' => $photoUrl,
                    'photo_out_url' => null,
                    'lat_in' => $cfg['lat'],
                    'lng_in' => $cfg['lng'],
                    'status' => $cfg['status'],
                    'late_minutes' => $cfg['late_minutes'],
                    'overtime_minutes' => 0,
                    'is_manual_override' => false,
                    'notes' => 'Presensi selfie kamera WebP terverifikasi GPS',
                ]
            );
        }

        // 6. Buat 1 Pengajuan Tukar Shift Pending untuk pengujian Owner/Manager
        $sitiUser = $createdUsers['siti.kasir@amorecoffee.id'] ?? null;
        $anisaUser = $createdUsers['anisa.barista@amorecoffee.id'] ?? null;

        if ($sitiUser && $anisaUser) {
            $tomorrow = Carbon::tomorrow();
            $tomorrowAssignment = ShiftAssignment::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'shift_template_id' => $shiftPagiSleman->id,
                    'assigned_user_id' => $sitiUser->id,
                    'date' => $tomorrow->toDateString(),
                ],
                [
                    'actual_user_id' => $anisaUser->id,
                    'is_swap' => true,
                    'swap_status' => 'PENDING',
                    'created_by_user_id' => $sitiUser->id,
                ]
            );
        }

        // 7. Buat 1 Pengajuan Kasbon Darurat Pending
        if ($sitiUser) {
            CashAdvance::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $sitiUser->id,
                    'request_date' => $today->toDateString(),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'amount' => 500000.00,
                    'status' => 'PENDING',
                ]
            );
        }
    }
}
