<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockWaste;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockWasteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * owner dapat mencatat kerugian stock waste via web portal dan melihat agregasi total kerugian
     */
    public function test_owner_can_record_and_list_stock_waste_via_web_portal(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $product = Product::where('workspace_id', $workspace->id)->first();

        $response = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/stock-wastes', [
                'branch_id' => $branch->id,
                'product_id' => $product?->id,
                'item_name' => 'Susu Fresh Milk Diamond 1L',
                'quantity' => 4,
                'unit' => 'Liter',
                'cost_per_unit' => 18000,
                'reason' => 'SPOILED',
                'notes' => 'Kulkas mati semalaman karena jeglek',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Pencatatan stock waste berhasil disimpan.',
                'data' => [
                    'item_name' => 'Susu Fresh Milk Diamond 1L',
                    'quantity' => '4.00',
                    'cost_per_unit' => '18000.00',
                    'total_loss_cost' => '72000.00',
                    'reason' => 'SPOILED',
                ],
            ]);

        $this->assertDatabaseHas('stock_wastes', [
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'item_name' => 'Susu Fresh Milk Diamond 1L',
            'total_loss_cost' => 72000,
        ]);

        $listRes = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/stock-wastes');

        $listRes->assertStatus(200)
            ->assertJson([
                'total_loss_cost' => 72000.0,
                'count' => 1,
            ]);
    }

    /**
     * perangkat POS tablet dapat mencatat barang terbuang / tumpah langsung dari meja kasir
     */
    public function test_pos_device_can_record_stock_waste(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/inventory/waste', [
                'item_name' => 'Sirup Caramel Monin 500ml',
                'quantity' => 1,
                'unit' => 'Botol',
                'cost_per_unit' => 135000,
                'reason' => 'ACCIDENT_SPILL',
                'cashier_user_id' => $cashier->id,
                'notes' => 'Botol tersenggol saat rush hour',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Pencatatan stock waste POS berhasil disimpan.',
                'data' => [
                    'item_name' => 'Sirup Caramel Monin 500ml',
                    'total_loss_cost' => '135000.00',
                    'reason' => 'ACCIDENT_SPILL',
                ],
            ]);

        // Verifikasi POS wastes listing
        $listRes = $this->withHeader('X-Device-Token', $rawToken)
            ->getJson('/api/v1/pos/inventory/waste');

        $listRes->assertStatus(200)
            ->assertJsonFragment(['item_name' => 'Sirup Caramel Monin 500ml']);
    }

    /**
     * staf atau manajer non-owner terisolasi secara ketat hanya pada cabang miliknya
     */
    public function test_non_owner_admin_is_strictly_scoped_to_own_branch_for_stock_waste(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branches = Branch::where('workspace_id', $workspace->id)->get();
        $branch1 = $branches[0];

        $branch2 = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Jakal KM 12',
            'lat' => -7.770000,
            'lng' => 110.370000,
            'radius_meters' => 100,
        ]);

        $managerUser = User::create([
            'name' => 'Manager Seturan',
            'email' => 'mgr.seturan@gmail.com',
            'password' => Hash::make('123456'),
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $managerUser->id,
            'role' => 'ADMIN',
            'branch_id' => $branch1->id,
        ]);

        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();

        // Buat data waste di cabang 2
        $wasteBranch2 = StockWaste::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch2->id,
            'item_name' => 'Beans House Blend 1kg',
            'quantity' => 2,
            'unit' => 'Kg',
            'cost_per_unit' => 120000,
            'total_loss_cost' => 240000,
            'reason' => 'EXPIRED',
            'recorded_by_user_id' => $owner->id,
        ]);

        // Manager cabang 1 mencoba melihat detail waste cabang 2 -> 403 Forbidden
        $showRes = $this->actingAs($managerUser)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson("/api/v1/stock-wastes/{$wasteBranch2->id}");

        $showRes->assertStatus(403);

        // Manager cabang 1 mencoba menghapus waste cabang 2 -> 403 Forbidden
        $delRes = $this->actingAs($managerUser)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->deleteJson("/api/v1/stock-wastes/{$wasteBranch2->id}");

        $delRes->assertStatus(403);
        $this->assertDatabaseHas('stock_wastes', ['id' => $wasteBranch2->id]);
    }
}
