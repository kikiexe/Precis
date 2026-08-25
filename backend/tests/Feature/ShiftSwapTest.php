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

class ShiftSwapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_can_request_shift_swap_to_another_staff(): void
    {
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'date' => Carbon::today()->addDays(2)->toDateString(),
            'is_swap' => false,
            'swap_status' => 'NONE',
        ]);

        Sanctum::actingAs($staff1);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/swap-requests', [
                'shift_assignment_id' => $assignment->id,
                'target_user_id' => $staff2->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Permohonan tukar shift berhasil diajukan.')
            ->assertJsonPath('data.is_swap', true)
            ->assertJsonPath('data.actual_user_id', $staff2->id)
            ->assertJsonPath('data.swap_status', 'PENDING');

        $this->assertDatabaseHas('shift_assignments', [
            'id' => $assignment->id,
            'is_swap' => true,
            'actual_user_id' => $staff2->id,
            'swap_status' => 'PENDING',
        ]);
    }

    public function test_staff_cannot_request_swap_for_another_users_shift(): void
    {
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        // jadwal milik staff 2
        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff2->id,
            'date' => Carbon::today()->addDays(2)->toDateString(),
            'is_swap' => false,
            'swap_status' => 'NONE',
        ]);

        // staff 1 mencoba mengajukan swap untuk jadwal staff 2
        Sanctum::actingAs($staff1);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/swap-requests', [
                'shift_assignment_id' => $assignment->id,
                'target_user_id' => $staff1->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_assignment_id']);
    }

    public function test_staff_cannot_request_swap_for_past_shift(): void
    {
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        // jadwal 2 hari yang lalu
        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'date' => Carbon::today()->subDays(2)->toDateString(),
            'is_swap' => false,
            'swap_status' => 'NONE',
        ]);

        Sanctum::actingAs($staff1);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/swap-requests', [
                'shift_assignment_id' => $assignment->id,
                'target_user_id' => $staff2->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_assignment_id']);
    }

    public function test_owner_and_admin_can_view_pending_swap_requests(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'kiki@gmail.com')->first();
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'actual_user_id' => $staff2->id,
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'is_swap' => true,
            'swap_status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/shifts/swap-requests');

        $response->assertOk()
            ->assertJsonPath('message', 'Daftar permohonan tukar shift berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'template' => ['id', 'name', 'branch_id', 'branch_name', 'expected_clock_in', 'expected_clock_out'],
                        'assigned_user' => ['id', 'name', 'email'],
                        'actual_user' => ['id', 'name', 'email'],
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_owner_and_admin_can_approve_swap_request(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'kiki@gmail.com')->first();
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'actual_user_id' => $staff2->id,
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'is_swap' => true,
            'swap_status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/shifts/swap-requests/{$assignment->id}/approve");

        $response->assertOk()
            ->assertJsonPath('message', 'Permohonan tukar shift berhasil disetujui.')
            ->assertJsonPath('data.swap_status', 'APPROVED')
            ->assertJsonPath('data.swap_approved_by_user_id', $owner->id);

        $this->assertDatabaseHas('shift_assignments', [
            'id' => $assignment->id,
            'swap_status' => 'APPROVED',
            'swap_approved_by_user_id' => $owner->id,
        ]);
    }

    public function test_owner_and_admin_can_reject_swap_request(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'kiki@gmail.com')->first();
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'actual_user_id' => $staff2->id,
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'is_swap' => true,
            'swap_status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/shifts/swap-requests/{$assignment->id}/reject");

        $response->assertOk()
            ->assertJsonPath('message', 'Permohonan tukar shift telah ditolak.')
            ->assertJsonPath('data.swap_status', 'REJECTED')
            ->assertJsonPath('data.actual_user_id', null);

        $this->assertDatabaseHas('shift_assignments', [
            'id' => $assignment->id,
            'swap_status' => 'REJECTED',
            'actual_user_id' => null,
            'swap_approved_by_user_id' => $owner->id,
        ]);
    }

    public function test_staff_cannot_approve_or_reject_swap_requests(): void
    {
        /** @var User $staff1 */
        $staff1 = User::where('email', 'ami@gmail.com')->first();
        /** @var User $staff2 */
        $staff2 = User::where('email', 'hani@gmail.com')->first();
        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $branch = Branch::where('workspace_id', $workspace->id)->where('name', 'like', '%Seturan%')->first();

        $template = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'name' => 'Shift Pagi',
            'expected_clock_in' => '07:00:00',
            'expected_clock_out' => '15:00:00',
        ]);

        $assignment = ShiftAssignment::create([
            'workspace_id' => $workspace->id,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $staff1->id,
            'actual_user_id' => $staff2->id,
            'date' => Carbon::today()->addDays(1)->toDateString(),
            'is_swap' => true,
            'swap_status' => 'PENDING',
        ]);

        Sanctum::actingAs($staff1);

        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/shifts/swap-requests/{$assignment->id}/approve")
            ->assertStatus(403);

        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/shifts/swap-requests/{$assignment->id}/reject")
            ->assertStatus(403);
    }
}
