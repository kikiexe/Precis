<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_new_workspace_and_becomes_owner(): void
    {
        /** @var User $user */
        $user = User::create([
            'email' => 'founder@example.com',
            'name' => 'Founder Kopi',
            'password' => bcrypt('Password123!'),
            'subscription_status' => 'TRIAL',
            'subscription_expires_at' => now()->addDays(14),
            'max_workspaces' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/workspaces', [
            'name' => 'Kopi Senja Artisan',
            'branch_name' => 'Outlet Palagan #01',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'workspace' => ['id', 'name', 'slug'],
                'branch' => ['id', 'name'],
                'role',
                'workspaces',
            ])
            ->assertJson([
                'workspace' => [
                    'name' => 'Kopi Senja Artisan',
                ],
                'branch' => [
                    'name' => 'Outlet Palagan #01',
                ],
                'role' => 'OWNER',
            ]);

        $workspaceId = $response->json('workspace.id');

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspaceId,
            'name' => 'Kopi Senja Artisan',
            'owner_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('branches', [
            'workspace_id' => $workspaceId,
            'name' => 'Outlet Palagan #01',
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'role' => 'OWNER',
            'is_active' => true,
        ]);
    }
}
