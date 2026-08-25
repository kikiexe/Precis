<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashAdvance;
use App\Models\Category;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiTenantLeakageTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspaceA;
    private Workspace $workspaceB;
    private User $ownerA;
    private User $ownerB;
    private User $staffA;
    private User $staffB;
    private PosTerminal $terminalA;
    private PosTerminal $terminalB;
    private string $deviceTokenA = 'pos-token-tenant-a';
    private string $deviceTokenB = 'pos-token-tenant-b';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // tenant A: Norde Coffee
        $this->workspaceA = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->ownerA = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->staffA = User::where('email', 'ami@gmail.com')->firstOrFail();

        $branchA = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspaceA->id)->firstOrFail();
        $this->terminalA = PosTerminal::create([
            'workspace_id' => $this->workspaceA->id,
            'branch_id' => $branchA->id,
            'terminal_name' => 'POS Terminal Tenant A',
            'device_token_hash' => hash('sha256', $this->deviceTokenA),
            'is_active' => true,
        ]);

        // tenant B: Kopi Senja (Workspace Kedua)
        $this->ownerB = User::create([
            'name' => 'Budi Senja Owner',
            'email' => 'budi.owner@kopisenja.id',
            'password' => 'password',
            'subscription_status' => 'ACTIVE',
        ]);

        $this->workspaceB = Workspace::create([
            'owner_user_id' => $this->ownerB->id,
            'name' => 'Kopi Senja Indonesia',
            'slug' => 'kopi-senja',
            'status' => 'ACTIVE',
        ]);

        $this->staffB = User::create([
            'name' => 'Rudi Barista Senja',
            'email' => 'rudi.barista@kopisenja.id',
            'password' => 'password',
        ]);

        $branchB = Branch::create([
            'workspace_id' => $this->workspaceB->id,
            'name' => 'Kopi Senja Gejayan',
            'lat' => -7.7600,
            'lng' => 110.3900,
            'radius_meters' => 50,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $this->workspaceB->id,
            'user_id' => $this->ownerB->id,
            'branch_id' => null,
            'role' => 'OWNER',
            'base_salary' => 5000000.00,
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $this->workspaceB->id,
            'user_id' => $this->staffB->id,
            'branch_id' => $branchB->id,
            'role' => 'STAFF',
            'base_salary' => 2400000.00,
            'is_active' => true,
        ]);

        $this->terminalB = PosTerminal::create([
            'workspace_id' => $this->workspaceB->id,
            'branch_id' => $branchB->id,
            'terminal_name' => 'POS Terminal Tenant B',
            'device_token_hash' => hash('sha256', $this->deviceTokenB),
            'is_active' => true,
        ]);

        // tambah produk rahasia untuk Tenant B
        $categoryB = Category::create([
            'workspace_id' => $this->workspaceB->id,
            'name' => 'Menu Rahasia Senja',
        ]);

        Product::create([
            'workspace_id' => $this->workspaceB->id,
            'category_id' => $categoryB->id,
            'name' => 'Senja Secret Recipe Coffee',
            'base_price' => 35000.00,
            'is_active' => true,
        ]);
    }

    public function test_tenant_a_cannot_access_tenant_b_products_catalog(): void
    {
        $response = $this->withHeader('X-Device-Token', $this->deviceTokenA)
            ->getJson('/api/v1/pos/products');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('Menu Rahasia Senja', $content);
        $this->assertStringNotContainsString('Senja Secret Recipe Coffee', $content);
    }

    public function test_tenant_a_cannot_view_or_approve_tenant_b_cash_advances(): void
    {
        // buat kasbon untuk staf Tenant B
        $advanceB = CashAdvance::create([
            'workspace_id' => $this->workspaceB->id,
            'user_id' => $this->staffB->id,
            'amount' => 300000.00,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);

        // owner A mencoba menyetujui kasbon milik tenant B dalam konteks workspace A
        Sanctum::actingAs($this->ownerA);

        $response = $this->withHeader('X-Workspace-Id', $this->workspaceA->id)
            ->postJson("/api/v1/admin/cash-advances/{$advanceB->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('errors.cash_advance_id.0', 'Permohonan kasbon tidak ditemukan atau sudah diproses.');

        // pastikan status kasbon tenant B tidak berubah
        $advanceB->refresh();
        $this->assertEquals('PENDING', $advanceB->status);
    }

    public function test_tenant_a_cannot_preview_tenant_b_payroll_data(): void
    {
        Sanctum::actingAs($this->ownerA);

        $response = $this->withHeader('X-Workspace-Id', $this->workspaceA->id)
            ->getJson('/api/v1/admin/payroll/preview');

        $response->assertOk();

        $items = $response->json('data.items');
        $userIds = collect($items)->pluck('user_id')->toArray();

        // pastikan tidak ada staf tenant B yang bocor ke rekap gaji tenant A
        $this->assertNotContains($this->ownerB->id, $userIds);
        $this->assertNotContains($this->staffB->id, $userIds);
    }

    public function test_user_cannot_access_workspace_context_without_membership(): void
    {
        // owner B mencoba mengakses konteks workspace A
        Sanctum::actingAs($this->ownerB);

        $response = $this->withHeader('X-Workspace-Id', $this->workspaceA->id)
            ->getJson('/api/v1/workspace/context');

        $response->assertStatus(403);
    }
}
