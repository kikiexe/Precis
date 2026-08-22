<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WallOfFacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_owner_and_admin_can_view_wall_of_faces_feed(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        // buat record presensi hari ini
        Attendance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'branch_id' => $branch->id,
            'clock_in_time' => Carbon::today()->setTime(7, 5, 0),
            'photo_in_url' => 'https://r2.precis.id/permanent/' . $workspace->id . '/selfie_siti.webp',
            'lat_in' => (float) $branch->lat,
            'lng_in' => (float) $branch->lng,
            'late_minutes' => 5,
            'status' => 'APPROVED',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/attendances/wall-of-faces');

        $response->assertOk()
            ->assertJsonPath('message', 'Feed Wall of Faces berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => ['id', 'name', 'email'],
                        'branch' => ['id', 'name'],
                        'clock_in_time',
                        'photo_in_url',
                        'late_minutes',
                        'status',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_staff_is_forbidden_from_viewing_wall_of_faces_feed(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/attendances/wall-of-faces');

        $response->assertStatus(403);
    }
}
