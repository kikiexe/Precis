<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AddonModifierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * user owner / admin dapat membuat kategori modifier baru beserta opsi item dan menghubungkan ke menu
     */
    public function test_owner_can_create_addon_category_with_options_and_attach_to_products(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        $category = Category::where('workspace_id', $workspace->id)->firstOrFail();
        $product1 = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Caffe Latte Special',
            'base_price' => 28000,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Cappuccino Special',
            'base_price' => 28000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/addon-categories', [
                'name' => 'Pilihan Susu',
                'selection_type' => 'SINGLE',
                'is_required' => true,
                'min_selection' => 1,
                'max_selection' => 1,
                'product_ids' => [$product1->id, $product2->id],
                'addons' => [
                    ['name' => 'Fresh Milk', 'price' => 0],
                    ['name' => 'Oat Milk Substitute', 'price' => 8000],
                    ['name' => 'Almond Milk', 'price' => 10000],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Kategori add-on berhasil dibuat.',
                'data' => [
                    'name' => 'Pilihan Susu',
                    'selection_type' => 'SINGLE',
                    'is_required' => true,
                ],
            ]);

        $this->assertDatabaseHas('addon_categories', [
            'workspace_id' => $workspace->id,
            'name' => 'Pilihan Susu',
            'selection_type' => 'SINGLE',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('addons', [
            'workspace_id' => $workspace->id,
            'name' => 'Oat Milk Substitute',
            'price' => 8000,
        ]);

        $this->assertDatabaseHas('product_addon_categories', [
            'product_id' => $product1->id,
        ]);
    }

    /**
     * user dapat menambahkan item add-on baru dan memperbaruinya
     */
    public function test_user_can_manage_individual_addons(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        $addonCategory = AddonCategory::create([
            'workspace_id' => $workspace->id,
            'name' => 'Extra Topping',
            'selection_type' => 'MULTIPLE',
            'is_required' => false,
        ]);

        // 1. Tambah add-on
        $createRes = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/addons', [
                'addon_category_id' => $addonCategory->id,
                'name' => 'Caramel Drizzle',
                'price' => 5000,
            ]);

        $createRes->assertStatus(201);
        $addonId = $createRes->json('data.id');

        // 2. Update add-on
        $updateRes = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson("/api/v1/addons/{$addonId}", [
                'name' => 'Salted Caramel Drizzle',
                'price' => 6000,
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Salted Caramel Drizzle',
                    'price' => 6000.0,
                ],
            ]);

        // 3. Hapus add-on
        $deleteRes = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->deleteJson("/api/v1/addons/{$addonId}");

        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('addons', ['id' => $addonId]);
    }
}
