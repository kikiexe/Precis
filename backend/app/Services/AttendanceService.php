<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly GeofenceService $geofenceService,
        private readonly MediaStorageService $mediaStorageService,
    ) {
    }

    /**
     * catat presensi masuk (clock-in) karyawan dengan verifikasi foto GPS dan jadwal shift
     */
    public function clockIn(
        User $user,
        string $workspaceId,
        string $branchId,
        float $lat,
        float $lng,
        string $photoUrl,
        ?string $notes = null
    ): Attendance {
        /** @var Branch|null $branch */
        $branch = Branch::where('workspace_id', $workspaceId)->where('id', $branchId)->first();
        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => ['Outlet cabang tidak ditemukan.'],
            ]);
        }

        // 1. validasi geofence radius
        $geofence = $this->geofenceService->validateBranchRadius($branch, $lat, $lng);
        if (! $geofence['is_valid']) {
            throw ValidationException::withMessages([
                'location' => [
                    sprintf(
                        'Presensi ditolak. Posisi Anda berjarak %s meter dari outlet (radius maksimum: %s meter).',
                        $geofence['distance_meters'],
                        $geofence['allowed_radius_meters']
                    ),
                ],
            ]);
        }

        // 2. periksa apakah sudah ada sesi presensi aktif yang belum clock-out
        $activeAttendance = Attendance::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->whereNull('clock_out_time')
            ->first();

        if ($activeAttendance) {
            throw ValidationException::withMessages([
                'attendance' => ['Anda masih memiliki sesi presensi aktif yang belum clock-out.'],
            ]);
        }

        $today = Carbon::today()->toDateString();

        // 3. cari jadwal shift aktif hari ini
        /** @var ShiftAssignment|null $shiftAssignment */
        $shiftAssignment = ShiftAssignment::withoutGlobalScopes()
            ->with('shiftTemplate')
            ->where('workspace_id', $workspaceId)
            ->whereDate('date', $today)
            ->where(function ($query) use ($user): void {
                $query->where('actual_user_id', $user->id)
                    ->orWhere(function ($q) use ($user): void {
                        $q->where('assigned_user_id', $user->id)
                            ->whereNull('actual_user_id');
                    });
            })
            ->first();

        // 4. kalkulasi menit keterlambatan
        $lateMinutes = 0;
        $now = Carbon::now();

        if ($shiftAssignment?->shiftTemplate) {
            $dateStr = $shiftAssignment->date instanceof Carbon
                ? $shiftAssignment->date->toDateString()
                : (string) $shiftAssignment->date;

            $expectedInStr = $dateStr . ' ' . $shiftAssignment->shiftTemplate->expected_clock_in;
            $expectedIn = Carbon::parse($expectedInStr);

            if ($now->greaterThan($expectedIn)) {
                $lateMinutes = (int) ceil($expectedIn->diffInSeconds($now) / 60);
            }
        }

        // 5. pindahkan file foto selfie dari folder sementara (staging) ke folder penyimpanan permanen
        $permanentPhotoUrl = $this->mediaStorageService->moveToPermanent($photoUrl, $workspaceId);

        // 6. buat record presensi masuk
        return Attendance::create([
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'shift_assignment_id' => $shiftAssignment?->id,
            'clock_in_time' => $now,
            'photo_in_url' => $permanentPhotoUrl,
            'lat_in' => $lat,
            'lng_in' => $lng,
            'late_minutes' => $lateMinutes,
            'status' => 'APPROVED',
            'notes' => $notes,
        ]);
    }

    /**
     * catat presensi keluar (clock-out) karyawan dan hitung jam lembur
     */
    public function clockOut(
        User $user,
        string $workspaceId,
        string $branchId,
        float $lat,
        float $lng,
        ?string $photoUrl = null,
        ?string $notes = null
    ): Attendance {
        /** @var Branch|null $branch */
        $branch = Branch::where('workspace_id', $workspaceId)->where('id', $branchId)->first();
        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => ['Outlet cabang tidak ditemukan.'],
            ]);
        }

        // 1. validasi geofence radius
        $geofence = $this->geofenceService->validateBranchRadius($branch, $lat, $lng);
        if (! $geofence['is_valid']) {
            throw ValidationException::withMessages([
                'location' => [
                    sprintf(
                        'Presensi keluar ditolak. Posisi Anda berjarak %s meter dari outlet (radius maksimum: %s meter).',
                        $geofence['distance_meters'],
                        $geofence['allowed_radius_meters']
                    ),
                ],
            ]);
        }

        // 2. cari sesi presensi masuk aktif
        /** @var Attendance|null $attendance */
        $attendance = Attendance::withoutGlobalScopes()
            ->with(['shiftAssignment.shiftTemplate'])
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->whereNull('clock_out_time')
            ->latest('clock_in_time')
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'attendance' => ['Tidak ditemukan sesi presensi masuk aktif untuk dilakukan clock-out.'],
            ]);
        }

        // 3. kalkulasi menit lembur
        $now = Carbon::now();
        $overtimeMinutes = 0;

        /** @var BranchSetting|null $setting */
        $setting = BranchSetting::where('workspace_id', $workspaceId)
            ->where(function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->first();

        $minThreshold = $setting?->min_overtime_threshold_minutes ?? 30;

        if ($attendance->shiftAssignment?->shiftTemplate) {
            $dateStr = $attendance->shiftAssignment->date instanceof Carbon
                ? $attendance->shiftAssignment->date->toDateString()
                : (string) $attendance->shiftAssignment->date;

            $expectedOutStr = $dateStr . ' ' . $attendance->shiftAssignment->shiftTemplate->expected_clock_out;
            $expectedOut = Carbon::parse($expectedOutStr);

            if ($now->greaterThan($expectedOut)) {
                $rawOvertime = (int) floor($expectedOut->diffInSeconds($now) / 60);
                if ($rawOvertime >= $minThreshold) {
                    $overtimeMinutes = $rawOvertime;
                }
            }
        }

        // 4. pindahkan foto keluar ke folder permanen jika disertakan
        $permanentPhotoOutUrl = null;
        if ($photoUrl) {
            $permanentPhotoOutUrl = $this->mediaStorageService->moveToPermanent($photoUrl, $workspaceId);
        }

        // 5. update data presensi keluar
        $attendance->update([
            'clock_out_time' => $now,
            'photo_out_url' => $permanentPhotoOutUrl,
            'lat_out' => $lat,
            'lng_out' => $lng,
            'overtime_minutes' => $overtimeMinutes,
            'notes' => $notes ?: $attendance->notes,
        ]);

        return $attendance;
    }

    /**
     * ambil feed presensi harian untuk antarmuka wall of faces dashboard admin
     */
    public function getWallOfFaces(string $workspaceId, ?string $branchId = null, ?string $date = null): array
    {
        $query = Attendance::withoutGlobalScopes()
            ->with(['user', 'branch', 'shiftAssignment.shiftTemplate'])
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('clock_in_time');

        if ($date) {
            $query->whereDate('clock_in_time', Carbon::parse($date)->toDateString());
        } else {
            $query->limit(100);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->map(function (Attendance $att): array {
            $shift = $att->shiftAssignment?->shiftTemplate;
            $status = ($att->late_minutes && $att->late_minutes > 0) ? 'LATE' : 'ON_TIME';

            return [
                'id' => $att->id,
                'user_id' => $att->user_id,
                'user_name' => $att->user?->name ?? 'Staf',
                'avatar_url' => $att->photo_in_url ?? '',
                'branch_id' => $att->branch_id,
                'branch_name' => $att->branch?->name ?? 'Cabang Utama',
                'shift_name' => $shift?->name ?? 'Shift Kerja',
                'clock_in_time' => $att->clock_in_time?->toIso8601String(),
                'clock_out_time' => $att->clock_out_time?->toIso8601String(),
                'photo_in_url' => $att->photo_in_url,
                'photo_out_url' => $att->photo_out_url,
                'late_minutes' => (int) ($att->late_minutes ?? 0),
                'overtime_minutes' => (int) ($att->overtime_minutes ?? 0),
                'status' => $status,
                'notes' => $att->notes,
                'created_at' => $att->clock_in_time?->toDateString(),
                'user' => [
                    'id' => $att->user?->id,
                    'name' => $att->user?->name,
                    'email' => $att->user?->email,
                ],
                'branch' => [
                    'id' => $att->branch?->id,
                    'name' => $att->branch?->name,
                ],
                'shift' => $shift ? [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'expected_clock_in' => $shift->expected_clock_in,
                    'expected_clock_out' => $shift->expected_clock_out,
                ] : null,
            ];
        })->toArray();
    }
}
