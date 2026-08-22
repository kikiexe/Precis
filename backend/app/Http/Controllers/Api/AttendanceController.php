<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Attendance\ClockInRequest;
use App\Http\Requests\Attendance\ClockOutRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {
    }

    /**
     * catat presensi masuk staf
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $attendance = $this->attendanceService->clockIn(
            user: $user,
            workspaceId: $workspaceId,
            branchId: $request->validated('branch_id'),
            lat: (float) $request->validated('latitude'),
            lng: (float) $request->validated('longitude'),
            photoUrl: $request->validated('photo_url'),
            notes: $request->validated('notes'),
        );

        return new JsonResponse([
            'message' => 'Presensi masuk berhasil dicatat.',
            'data' => [
                'id' => $attendance->id,
                'branch_id' => $attendance->branch_id,
                'clock_in_time' => $attendance->clock_in_time?->toIso8601String(),
                'late_minutes' => $attendance->late_minutes,
                'status' => $attendance->status,
                'photo_in_url' => $attendance->photo_in_url,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * catat presensi keluar staf
     */
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $attendance = $this->attendanceService->clockOut(
            user: $user,
            workspaceId: $workspaceId,
            branchId: $request->validated('branch_id'),
            lat: (float) $request->validated('latitude'),
            lng: (float) $request->validated('longitude'),
            photoUrl: $request->validated('photo_url'),
            notes: $request->validated('notes'),
        );

        return new JsonResponse([
            'message' => 'Presensi keluar berhasil dicatat.',
            'data' => [
                'id' => $attendance->id,
                'branch_id' => $attendance->branch_id,
                'clock_out_time' => $attendance->clock_out_time?->toIso8601String(),
                'overtime_minutes' => $attendance->overtime_minutes,
                'photo_out_url' => $attendance->photo_out_url,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil feed visual audit foto presensi wall of faces (khusus OWNER dan ADMIN).
     */
    public function wallOfFaces(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $branchId = $request->query('branch_id');
        $date = $request->query('date');

        $feed = $this->attendanceService->getWallOfFaces(
            workspaceId: $workspaceId,
            branchId: $branchId ? (string) $branchId : null,
            date: $date ? (string) $date : null,
        );

        return new JsonResponse([
            'message' => 'Feed Wall of Faces berhasil dimuat.',
            'data' => $feed,
        ], Response::HTTP_OK);
    }
}
