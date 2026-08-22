<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CashAdvance;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashAdvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_can_request_cash_advance(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/cash-advances', [
                'amount' => 500000,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Permohonan kasbon berhasil diajukan.')
            ->assertJsonPath('data.amount', 500000)
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('cash_advances', [
            'id' => $response->json('data.id'),
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 500000.00,
            'status' => 'PENDING',
        ]);
    }

    public function test_staff_cannot_request_cash_advance_with_invalid_amount(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/cash-advances', [
                'amount' => 500, // di bawah minimum 10.000
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_staff_cannot_request_cash_advance_while_having_pending_request(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        // buat kasbon yang masih pending
        CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 300000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/cash-advances', [
                'amount' => 200000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_staff_can_view_own_cash_advance_history(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 300000,
            'request_date' => Carbon::today()->subDays(5)->toDateString(),
            'status' => 'APPROVED',
        ]);

        CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 200000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/cash-advances/my');

        $response->assertOk()
            ->assertJsonPath('message', 'Riwayat kasbon berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'request_date',
                        'status',
                        'approved_by',
                        'deducted_at_payroll_date',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_owner_and_admin_can_view_all_cash_advances(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 300000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/cash-advances?status=PENDING');

        $response->assertOk()
            ->assertJsonPath('message', 'Daftar pengajuan kasbon berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => ['id', 'name', 'email'],
                        'amount',
                        'request_date',
                        'status',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_owner_and_admin_can_approve_cash_advance(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        $advance = CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 450000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/cash-advances/{$advance->id}/approve");

        $response->assertOk()
            ->assertJsonPath('message', 'Permohonan kasbon berhasil disetujui.')
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.approved_by_user_id', $owner->id);

        $this->assertDatabaseHas('cash_advances', [
            'id' => $advance->id,
            'status' => 'APPROVED',
            'approved_by_user_id' => $owner->id,
        ]);
    }

    public function test_owner_and_admin_can_reject_cash_advance(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        $advance = CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 450000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/cash-advances/{$advance->id}/reject");

        $response->assertOk()
            ->assertJsonPath('message', 'Permohonan kasbon telah ditolak.')
            ->assertJsonPath('data.status', 'REJECTED')
            ->assertJsonPath('data.approved_by_user_id', $owner->id);

        $this->assertDatabaseHas('cash_advances', [
            'id' => $advance->id,
            'status' => 'REJECTED',
            'approved_by_user_id' => $owner->id,
        ]);
    }

    public function test_staff_cannot_approve_or_reject_cash_advance(): void
    {
        /** @var User $staff */
        $staff = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        $advance = CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staff->id,
            'amount' => 450000,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($staff);

        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/cash-advances/{$advance->id}/approve")
            ->assertStatus(403);

        $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson("/api/v1/admin/cash-advances/{$advance->id}/reject")
            ->assertStatus(403);
    }
}
