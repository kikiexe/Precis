<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\WorkspaceInvitationMailable;
use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->firstOrFail();
    }

    public function test_owner_can_invite_new_staff_and_email_is_sent(): void
    {
        Mail::fake();
        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/admin/invitations', [
                'email' => 'calon.barista@gmail.com',
                'job_title' => 'Senior Barista',
                'role' => 'STAFF',
                'base_salary' => 3500000.00,
                'branch_id' => $this->branch->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'calon.barista@gmail.com')
            ->assertJsonPath('data.job_title', 'Senior Barista')
            ->assertJsonPath('data.role', 'STAFF')
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $this->workspace->id,
            'email' => 'calon.barista@gmail.com',
            'job_title' => 'Senior Barista',
            'status' => 'PENDING',
        ]);

        Mail::assertSent(WorkspaceInvitationMailable::class, function (WorkspaceInvitationMailable $mail): bool {
            return $mail->hasTo('calon.barista@gmail.com');
        });
    }

    public function test_cannot_invite_existing_active_member(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/admin/invitations', [
                'email' => 'ami@gmail.com',
                'job_title' => 'Kasir',
                'role' => 'STAFF',
                'base_salary' => 2800000.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_owner_can_list_and_cancel_pending_invitations(): void
    {
        Sanctum::actingAs($this->owner);

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'invited_by_user_id' => $this->owner->id,
            'email' => 'pending.staff@gmail.com',
            'job_title' => 'Junior Cook',
            'role' => 'STAFF',
            'base_salary' => 2500000.00,
            'token' => 'test-invitation-token-12345678901234567890123456789012345678901234',
            'status' => 'PENDING',
            'expires_at' => now()->addDays(7),
        ]);

        $listResponse = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/admin/invitations');

        $listResponse->assertOk()
            ->assertJsonPath('data.0.email', 'pending.staff@gmail.com');

        $cancelResponse = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->deleteJson("/api/v1/admin/invitations/{$invitation->id}");

        $cancelResponse->assertOk()
            ->assertJsonPath('message', 'Undangan berhasil dibatalkan.');

        $this->assertDatabaseHas('workspace_invitations', [
            'id' => $invitation->id,
            'status' => 'CANCELLED',
        ]);
    }

    public function test_public_user_can_verify_and_accept_invitation(): void
    {
        $token = 'valid-token-for-invitation-accept-test-1234567890123456789012345678';
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'invited_by_user_id' => $this->owner->id,
            'email' => 'new.joiner@gmail.com',
            'job_title' => 'Head Roaster',
            'role' => 'MANAGER',
            'base_salary' => 4000000.00,
            'branch_id' => $this->branch->id,
            'token' => $token,
            'status' => 'PENDING',
            'expires_at' => now()->addDays(7),
        ]);

        // 1. Cek detail token undangan publik
        $verifyResponse = $this->getJson("/api/v1/invitations/{$token}");
        $verifyResponse->assertOk()
            ->assertJsonPath('data.email', 'new.joiner@gmail.com')
            ->assertJsonPath('data.job_title', 'Head Roaster')
            ->assertJsonPath('data.role', 'MANAGER');

        // 2. Terima undangan dengan membuat akun baru
        $acceptResponse = $this->postJson("/api/v1/invitations/{$token}/accept", [
            'name' => 'Fajar Pratama',
            'password' => 'SecurePass123!',
        ]);

        $acceptResponse->assertOk()
            ->assertJsonPath('data.user.email', 'new.joiner@gmail.com')
            ->assertJsonPath('data.member.role', 'MANAGER')
            ->assertJsonPath('data.member.job_title', 'Head Roaster');

        $this->assertDatabaseHas('workspace_invitations', [
            'id' => $invitation->id,
            'status' => 'ACCEPTED',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new.joiner@gmail.com',
            'name' => 'Fajar Pratama',
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $this->workspace->id,
            'role' => 'MANAGER',
            'job_title' => 'Head Roaster',
            'is_active' => true,
        ]);
    }
}
