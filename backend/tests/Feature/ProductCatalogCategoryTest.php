<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCatalogCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;
    private Branch $branch;
    private PosTerminal $posTerminal;
    private string $deviceToken = 'mock-pos-terminal-test-token';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->firstOrFail();

        $this->posTerminal = PosTerminal::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'terminal_name' => 'Tablet Kasir Uji',
            'device_token_hash' => hash('sha256', $this->deviceToken),
            'is_active' => true,
        ]);
    }

    public function test_user_can_create_custom_categories_such_as_pastry_and_espresso(): void
    {
        Sanctum::actingAs($this->owner);

        // 1. Tambah kategori baru: Signature Mocktails
        $mocktailRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/categories', [
                'name' => 'Signature Mocktails',
            ]);

        $mocktailRes->assertCreated()
            ->assertJsonPath('message', 'Kategori berhasil dibuat.')
            ->assertJsonPath('data.name', 'Signature Mocktails')
            ->assertJsonPath('data.type', 'MENU')
            ->assertJsonPath('data.item_count', 0);

        $mocktailId = $mocktailRes->json('data.id');

        // 2. Tambah kategori baru: Artisan Gelato
        $gelatoRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/categories', [
                'name' => 'Artisan Gelato',
            ]);

        $gelatoRes->assertCreated()
            ->assertJsonPath('data.name', 'Artisan Gelato');

        $gelatoId = $gelatoRes->json('data.id');

        // 3. Tambah produk di bawah kategori Mocktails & Gelato
        $sunsetRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/products', [
                'name' => 'Sunset Tropical Fizz',
                'category_id' => $mocktailId,
                'price' => 32000,
                'description' => 'Refreshing sparkling drink with passion fruit',
                'is_available' => true,
            ]);
        $sunsetRes->assertCreated();

        $pistachioRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/products', [
                'name' => 'Sicilian Pistachio Scoop',
                'category_id' => $gelatoId,
                'price' => 28000,
                'is_available' => true,
            ]);
        $pistachioRes->assertCreated();

        // 4. Pastikan GET /categories mencerminkan jumlah item produk yang benar
        $listRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/categories');

        $listRes->assertOk();

        $categories = collect($listRes->json('data'));
        $mocktailItem = $categories->firstWhere('id', $mocktailId);
        $gelatoItem = $categories->firstWhere('id', $gelatoId);

        $this->assertNotNull($mocktailItem);
        $this->assertEquals(1, $mocktailItem['item_count']);

        $this->assertNotNull($gelatoItem);
        $this->assertEquals(1, $gelatoItem['item_count']);

        // 5. Pastikan POS Terminal dapat mendownload katalog menu kategori baru
        $posCatalogRes = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->getJson('/api/v1/pos/products');

        $posCatalogRes->assertOk()
            ->assertJsonPath('message', 'Katalog produk POS berhasil dimuat.');

        $catalog = collect($posCatalogRes->json('data'));
        $this->assertTrue($catalog->contains('name', 'Signature Mocktails'));
        $this->assertTrue($catalog->contains('name', 'Artisan Gelato'));

        $allProducts = $catalog->pluck('products')->flatten(1);
        $this->assertTrue($allProducts->contains('name', 'Sunset Tropical Fizz'));
        $this->assertTrue($allProducts->contains('name', 'Sicilian Pistachio Scoop'));
    }

    public function test_category_deletion_is_blocked_when_active_products_exist(): void
    {
        Sanctum::actingAs($this->owner);

        // Buat kategori
        $catRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/categories', ['name' => 'Dessert']);
        $catId = $catRes->json('data.id');

        // Buat produk di kategori tersebut
        $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/products', [
                'name' => 'Tiramisu Cake',
                'category_id' => $catId,
                'price' => 35000,
            ])->assertCreated();

        // Coba hapus kategori yang masih memiliki produk -> HARUS DITOLAK 422
        $deleteRes = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->deleteJson("/api/v1/categories/{$catId}");

        $deleteRes->assertStatus(422)
            ->assertJsonPath('message', 'Kategori tidak dapat dihapus karena masih memiliki produk aktif.');

        $this->assertDatabaseHas('categories', ['id' => $catId]);
    }
}
