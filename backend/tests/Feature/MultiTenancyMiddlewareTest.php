<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiTenancyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_access_with_valid_workspace_header_succeeds(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/workspace/context');

        $response->assertOk()
            ->assertJsonPath('workspace.id', $workspace->id)
            ->assertJsonPath('member.role', 'OWNER');
    }

    public function test_access_without_workspace_header_returns_bad_request(): void
    {
        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/workspace/context');

        $response->assertStatus(400)
            ->assertJson(['message' => 'Header X-Workspace-Id wajib disertakan.']);
    }

    public function test_access_to_unauthorized_workspace_returns_forbidden(): void
    {
        // Buat workspace independen lain untuk pengujian isolasi
        $otherUser = User::create([
            'name' => 'Stranger',
            'email' => 'stranger@othercafe.id',
            'password' => 'secret',
        ]);
        $otherWorkspace = Workspace::create([
            'name' => 'Other Cafe',
            'slug' => 'other-cafe',
            'owner_user_id' => $otherUser->id,
        ]);

        /** @var User $owner */
        $owner = User::where('email', 'arief@amorecoffee.id')->first();
        Sanctum::actingAs($owner);

        // Owner Arief mencoba mengakses workspace milik orang lain
        $response = $this->withHeader('X-Workspace-Id', $otherWorkspace->id)
            ->getJson('/api/v1/workspace/context');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke workspace ini.']);
    }

    public function test_role_middleware_allows_owner_and_admin_for_admin_endpoint(): void
    {
        /** @var User $manager */
        $manager = User::where('email', 'budi.manager@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($manager);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/workspace/admin-only');

        $response->assertOk()
            ->assertJsonPath('member_role', 'ADMIN');
    }

    public function test_role_middleware_rejects_staff_for_admin_endpoint(): void
    {
        /** @var User $cashier */
        $cashier = User::where('email', 'siti.kasir@amorecoffee.id')->first();
        $workspace = Workspace::where('slug', 'amore-coffee')->first();

        Sanctum::actingAs($cashier);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/workspace/admin-only');

        $response->assertStatus(403)
            ->assertJsonStructure(['message', 'allowed_roles']);
    }
}
