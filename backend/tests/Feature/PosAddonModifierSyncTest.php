<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosAddonModifierSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * terminal POS dapat mengambil data produk dengan addon_category_ids dan master /pos/addons
     */
    public function test_pos_can_fetch_catalog_with_addons_and_dedicated_addons_endpoint(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        $category = Category::where('workspace_id', $workspace->id)->firstOrFail();
        $product = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Signature Latte',
            'base_price' => 30000,
            'is_active' => true,
        ]);

        $addonCat = AddonCategory::create([
            'workspace_id' => $workspace->id,
            'name' => 'Pilihan Susu',
            'selection_type' => 'SINGLE',
            'is_required' => true,
        ]);

        $addon = Addon::create([
            'workspace_id' => $workspace->id,
            'addon_category_id' => $addonCat->id,
            'name' => 'Oat Milk',
            'price' => 7000,
            'is_active' => true,
        ]);

        $product->addonCategories()->sync([$addonCat->id]);

        // 1. Cek GET /pos/products
        $prodRes = $this->withHeader('X-Device-Token', $rawToken)
            ->getJson('/api/v1/pos/products');

        $prodRes->assertStatus(200);
        $foundProd = collect($prodRes->json('data'))
            ->flatMap(fn ($c) => $c['products'])
            ->firstWhere('id', $product->id);

        $this->assertNotNull($foundProd);
        $this->assertContains($addonCat->id, $foundProd['addon_category_ids']);

        // 2. Cek GET /pos/addons
        $addonRes = $this->withHeader('X-Device-Token', $rawToken)
            ->getJson('/api/v1/pos/addons');

        $addonRes->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'selection_type',
                        'is_required',
                        'product_ids',
                        'addons' => [
                            '*' => ['id', 'name', 'price', 'is_active'],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * sinkronisasi offline batch memverifikasi harga add-on resmi server dan menyimpan snapshot modifiers
     */
    public function test_pos_sync_batch_recalculates_addon_prices_and_stores_modifiers_json(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        $session = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'opened_by_user_id' => $cashier->id,
            'opening_cash' => 200000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $category = Category::where('workspace_id', $workspace->id)->firstOrFail();
        $product = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Cold Brew Nitro',
            'base_price' => 35000,
            'is_active' => true,
        ]);

        $addonCat = AddonCategory::create([
            'workspace_id' => $workspace->id,
            'name' => 'Syrup Flavor',
            'selection_type' => 'MULTIPLE',
            'is_required' => false,
        ]);

        $addonVanilla = Addon::create([
            'workspace_id' => $workspace->id,
            'addon_category_id' => $addonCat->id,
            'name' => 'Vanilla Syrup',
            'price' => 5000,
            'is_active' => true,
        ]);

        $addonHazelnut = Addon::create([
            'workspace_id' => $workspace->id,
            'addon_category_id' => $addonCat->id,
            'name' => 'Hazelnut Syrup',
            'price' => 6000,
            'is_active' => true,
        ]);

        $clientOrderId = (string) Str::uuid();

        // Klien offline mengirim order dengan harga yang dimanipulasi (misal mencoba mengirim harga add-on Rp 0)
        $payload = [
            'orders' => [
                [
                    'client_order_id' => $clientOrderId,
                    'order_number' => 'ORD-OFFLINE-MOD-01',
                    'pos_session_id' => $session->id,
                    'cashier_user_id' => $cashier->id,
                    'order_type' => 'TAKE_AWAY',
                    'total_amount' => 35000, // manipulasi: hanya base price
                    'discount_amount' => 0,
                    'final_amount' => 35000,
                    'payment_method' => 'CASH',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => 2,
                            'unit_price' => 35000, // manipulasi
                            'subtotal' => 70000,
                            'notes' => 'Less ice',
                            'modifiers' => [
                                [
                                    'addon_id' => $addonVanilla->id,
                                    'addon_name' => 'Vanilla Syrup',
                                    'price' => 0, // manipulasi client
                                ],
                                [
                                    'addon_id' => $addonHazelnut->id,
                                    'addon_name' => 'Hazelnut Syrup',
                                    'price' => 0, // manipulasi client
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sinkronisasi transaksi pesanan POS berhasil diproses.',
                'data' => [
                    'synced_count' => 1,
                ],
            ]);

        $order = Order::where('client_order_id', $clientOrderId)->firstOrFail();

        // Verifikasi harga server-side:
        // Base Price = 35.000 + Addon Vanilla (5.000) + Addon Hazelnut (6.000) = 46.000 / item
        // Qty: 2 -> Total = 92.000
        $this->assertEquals(92000.0, (float) $order->total_amount);
        $this->assertEquals(92000.0, (float) $order->final_amount);

        $orderItem = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals(46000.0, (float) $orderItem->unit_price);
        $this->assertEquals(92000.0, (float) $orderItem->subtotal);

        // Verifikasi snapshot modifier JSON
        $this->assertIsArray($orderItem->modifiers);
        $this->assertCount(2, $orderItem->modifiers);
        $this->assertEquals('Vanilla Syrup', $orderItem->modifiers[0]['name']);
        $this->assertEquals(5000.0, (float) $orderItem->modifiers[0]['price']);
        $this->assertEquals('Hazelnut Syrup', $orderItem->modifiers[1]['name']);
        $this->assertEquals(6000.0, (float) $orderItem->modifiers[1]['price']);
    }
}
