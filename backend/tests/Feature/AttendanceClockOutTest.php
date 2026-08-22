<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_clock_out_and_calculate_overtime_minutes(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        // konfigurasi ambang batas lembur 30 menit
        BranchSetting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'branch_id' => $branch->id],
            ['min_overtime_threshold_minutes' => 30]
        );

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi Uji',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        Sanctum::actingAs($user);

        // 1. absen jam 07:00
        Carbon::setTestNow(Carbon::today()->setTime(7, 0, 0));
        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_in.webp',
            ])->assertStatus(201);

        // 2. absen jam 16:00 (lembur 60 menit, melebihi 30 menit)
        Carbon::setTestNow(Carbon::today()->setTime(16, 0, 0));
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-out', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_out.webp',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Presensi keluar berhasil dicatat.')
            ->assertJsonPath('data.overtime_minutes', 60);

        Carbon::setTestNow(); // reset waktu mock
    }

    public function test_clock_out_without_active_clock_in_is_rejected(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-out', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendance']);
    }

    public function test_clock_out_rejected_when_outside_geofence_radius(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        // absen valid terlebih dahulu
        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_in.webp',
            ])->assertStatus(201);

        // absen keluar di luar jangkauan (radius 10km)
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-out', [
                'branch_id' => $branch->id,
                'latitude' => -7.90000000,
                'longitude' => 110.50000000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }
}
