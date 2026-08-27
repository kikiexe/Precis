<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceCustomRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
    }

    public function test_owner_can_list_roles_in_workspace(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'roles' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'is_system',
                        'members_count',
                        'permissions',
                    ],
                ],
            ]);
    }

    public function test_owner_can_get_permissions_catalog(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/roles/permissions-catalog');

        $response->assertOk()
            ->assertJsonStructure([
                'modules' => [
                    'katalog',
                    'inventaris',
                    'operasional',
                    'keuangan',
                    'tim',
                    'pos',
                ],
                'presets',
            ]);
    }

    public function test_owner_can_create_custom_role_with_granular_permissions(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'name' => 'Head Barista & Roaster',
            'description' => 'Bertanggung jawab atas resep kopi, menu, dan opname biji kopi',
            'permissions' => [
                'catalog.view',
                'catalog.manage',
                'inventory.view',
                'inventory.adjust',
            ],
        ];

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/roles', $payload);

        $response->assertCreated()
            ->assertJsonPath('role.name', 'Head Barista & Roaster')
            ->assertJsonPath('role.is_system', false);

        $this->assertEqualsCanonicalizing([
            'catalog.view',
            'catalog.manage',
            'inventory.view',
            'inventory.adjust',
        ], $response->json('role.permissions'));

        $this->assertDatabaseHas('workspace_roles', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Head Barista & Roaster',
            'is_system' => false,
        ]);
    }

    public function test_cannot_create_role_with_name_owner(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/roles', [
                'name' => 'OWNER',
                'permissions' => ['catalog.view'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_custom_role_member_permission_enforcement(): void
    {
        // 1. Buat role kustom hanya dengan hak kelola katalog (tidak ada izin payroll)
        $role = WorkspaceRole::withoutGlobalScopes()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Menu Designer',
            'description' => 'Hanya kelola katalog menu',
            'is_system' => false,
        ]);

        $role->permissions()->create(['permission' => 'catalog.manage']);
        $role->permissions()->create(['permission' => 'catalog.view']);

        // 2. Buat user dan assign ke role ini
        $designerUser = User::create([
            'name' => 'Budi Graphic',
            'email' => 'budi.designer@gmail.com',
            'password' => bcrypt('123456'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $designerUser->id,
            'role_id' => $role->id,
            'role' => $role->name,
            'is_active' => true,
        ]);

        Sanctum::actingAs($designerUser);

        // A. Coba tambah kategori produk (Izin catalog.manage ada -> HARUS BERHASIL)
        $categoryRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/categories', [
                'name' => 'Specialty Filter Coffee',
            ]);
        $categoryRes->assertCreated();

        // B. Coba akses payroll preview (Izin payroll.view TIDAK ADA -> HARUS 403 FORBIDDEN)
        $payrollRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/admin/payroll/preview?period_start=2026-08-01&period_end=2026-08-31');
        $payrollRes->assertForbidden();
    }



    public function test_cannot_delete_role_with_active_members(): void
    {
        Sanctum::actingAs($this->owner);

        $customRole = WorkspaceRole::withoutGlobalScopes()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Floor Captain',
            'is_system' => false,
        ]);

        $captain = User::create([
            'name' => 'Captain Jack',
            'email' => 'jack@gmail.com',
            'password' => bcrypt('123456'),
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $captain->id,
            'role_id' => $customRole->id,
            'role' => $customRole->name,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->deleteJson("/api/v1/roles/{$customRole->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_can_delete_unused_custom_role(): void
    {
        Sanctum::actingAs($this->owner);

        $customRole = WorkspaceRole::withoutGlobalScopes()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Temporary Internship',
            'is_system' => false,
        ]);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->deleteJson("/api/v1/roles/{$customRole->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Peran kustom berhasil dihapus.');

        $this->assertDatabaseMissing('workspace_roles', [
            'id' => $customRole->id,
        ]);
    }
}
