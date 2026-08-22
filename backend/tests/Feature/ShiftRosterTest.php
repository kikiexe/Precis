<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShiftRosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_view_shift_roster_calendar_filtered_by_branch_and_dates(): void
    {
        /** @var User $user */
        $user = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        Sanctum::actingAs($user);

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->addDays(6)->toDateString();

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson("/api/v1/shifts/roster?branch_id={$branch->id}&start_date={$startDate}&end_date={$endDate}");

        $response->assertOk()
            ->assertJsonPath('message', 'Kalender roster shift berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'is_swap',
                        'swap_status',
                        'template' => ['id', 'name', 'branch_id', 'branch_name', 'expected_clock_in', 'expected_clock_out'],
                        'assigned_user' => ['id', 'name', 'email'],
                        'actual_user',
                        'swap_approved_by',
                    ],
                ],
            ]);
    }

    public function test_owner_and_admin_can_assign_shift_to_staff(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Sore Khusus',
            'expected_clock_in' => '15:00:00',
            'expected_clock_out' => '23:00:00',
        ]);

        Sanctum::actingAs($owner);

        $targetDate = Carbon::today()->addDays(2)->toDateString();

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/assign', [
                'shift_template_id' => $template->id,
                'assigned_user_id' => $staff->id,
                'date' => $targetDate,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Jadwal shift berhasil ditetapkan.')
            ->assertJsonPath('data.assigned_user_id', $staff->id)
            ->assertJsonPath('data.is_swap', false)
            ->assertJsonPath('data.swap_status', 'NONE');

        $this->assertDatabaseHas('shift_assignments', [
            'id' => $response->json('data.id'),
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff->id,
        ]);
    }

    public function test_assign_shift_fails_when_duplicate_shift_assigned_on_same_date(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        $template1 = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        $template2 = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Malam',
            'expected_clock_in' => '23:00:00',
            'expected_clock_out' => '07:00:00',
        ]);

        $targetDate = Carbon::today()->addDays(3)->toDateString();

        Sanctum::actingAs($owner);

        // penugasan pertama berhasil
        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/assign', [
                'shift_template_id' => $template1->id,
                'assigned_user_id' => $staff->id,
                'date' => $targetDate,
            ])->assertStatus(201);

        // penugasan kedua untuk staf yang sama di hari yang sama harus ditolak
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/assign', [
                'shift_template_id' => $template2->id,
                'assigned_user_id' => $staff->id,
                'date' => $targetDate,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_user_id']);
    }

    public function test_staff_role_cannot_assign_shifts(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Sleman%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/assign', [
                'shift_template_id' => $template->id,
                'assigned_user_id' => $staff->id,
                'date' => Carbon::today()->addDays(4)->toDateString(),
            ]);

        $response->assertStatus(403);
    }
}
