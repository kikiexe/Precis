<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_clock_in_within_branch_geofence_radius(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        // koordinat tepat di cabang Sleman (radius 50m)
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_siti.webp',
                'notes' => 'Presensi shift pagi',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Presensi masuk berhasil dicatat.')
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('attendances', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_clock_in_rejected_when_outside_geofence_radius(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        // koordinat 5km jauhnya dari lokasi outlet Sleman
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => -7.85000000,
                'longitude' => 110.45000000,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_siti.webp',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }

    public function test_clock_in_calculates_late_minutes_accurately_when_past_shift_start(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        // buat template shift jam 07:00 dan assignment untuk hari ini
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

        // simulasikan waktu saat ini jam 07:15 (telat 15 menit)
        Carbon::setTestNow(Carbon::today()->setTime(7, 15, 0));

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_siti.webp',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.late_minutes', 15);

        Carbon::setTestNow(); // reset waktu mock
    }

    public function test_duplicate_active_clock_in_is_rejected(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        // absen pertama
        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_siti.webp',
            ])->assertStatus(201);

        // absen kedua tanpa clock out sebelumnya
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/attendances/clock-in', [
                'branch_id' => $branch->id,
                'latitude' => (float) $branch->lat,
                'longitude' => (float) $branch->lng,
                'photo_url' => 'https://r2.precis.id/staging/' . $workspace->id . '/selfie_siti.webp',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendance']);
    }
}
