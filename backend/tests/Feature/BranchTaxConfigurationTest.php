<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchTaxConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * owner dapat mengatur konfigurasi pajak kustom dinamis (misal PB1 14% inclusive) pada cabang
     */
    public function test_owner_can_configure_custom_tax_rate_and_type_on_branch(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $response = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson("/api/v1/branches/{$branch->id}", [
                'tax_enabled' => true,
                'tax_name' => 'PB1 Resto (14%)',
                'tax_rate' => 14.00,
                'tax_type' => 'INCLUSIVE',
                'show_tax_on_receipt' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pengaturan cabang berhasil diperbarui.',
                'data' => [
                    'id' => $branch->id,
                    'tax_enabled' => true,
                    'tax_name' => 'PB1 Resto (14%)',
                    'tax_rate' => 14.0,
                    'tax_type' => 'INCLUSIVE',
                    'show_tax_on_receipt' => true,
                ],
            ]);

        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $branch->id,
            'tax_enabled' => 1,
            'tax_name' => 'PB1 Resto (14%)',
            'tax_rate' => 14.00,
            'tax_type' => 'INCLUSIVE',
        ]);
    }

    /**
     * endpoint pairing dan terminal info POS menyertakan konfigurasi pajak cabang
     */
    public function test_pos_terminal_info_includes_branch_tax_settings(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        // setel pajak cabang menjadi aktif 10%
        BranchSetting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'branch_id' => $branch->id],
            [
                'tax_enabled' => true,
                'tax_name' => 'PB1',
                'tax_rate' => 10.00,
                'tax_type' => 'INCLUSIVE',
                'show_tax_on_receipt' => true,
            ]
        );

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->getJson('/api/v1/pos/terminal-info');

        $response->assertStatus(200)
            ->assertJson([
                'branch_id' => $branch->id,
                'tax_settings' => [
                    'tax_enabled' => true,
                    'tax_name' => 'PB1',
                    'tax_rate' => 10.0,
                    'tax_type' => 'INCLUSIVE',
                    'show_tax_on_receipt' => true,
                ],
            ]);
    }

    /**
     * sinkronisasi pesanan batch menghitung pajak INCLUSIVE secara dinamis dan akurat di sisi server
     */
    public function test_sync_orders_batch_calculates_tax_inclusive_accurately(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        // Konfigurasi pajak cabang: 14% INCLUSIVE
        BranchSetting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'branch_id' => $branch->id],
            [
                'tax_enabled' => true,
                'tax_name' => 'PB1 Restoran (14%)',
                'tax_rate' => 14.00,
                'tax_type' => 'INCLUSIVE',
            ]
        );

        $product = Product::where('workspace_id', $workspace->id)->firstOrFail();
        $product->update(['base_price' => 100000]);
        $clientOrderId1 = (string) \Illuminate\Support\Str::uuid();

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/orders/sync-batch', [
                'orders' => [
                    [
                        'client_order_id' => $clientOrderId1,
                        'order_number' => 'ORD-TAX-001',
                        'total_amount' => 100000,
                        'discount_amount' => 0,
                        'final_amount' => 100000,
                        'payment_method' => 'CASH',
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'quantity' => 1,
                                'unit_price' => 100000,
                                'subtotal' => 100000,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'synced_count' => 1,
                ],
            ]);

        $order = Order::withoutGlobalScopes()->where('client_order_id', $clientOrderId1)->firstOrFail();

        // Pajak Inclusive: Final amount tetap 100.000, tax_amount = 100.000 - (100.000 / 1.14) = 12280.70
        $this->assertEquals(100000.0, (float) $order->final_amount);
        $this->assertEquals(14.0, (float) $order->tax_rate);
        $this->assertEquals('INCLUSIVE', $order->tax_type);
        $this->assertEquals(12280.70, (float) $order->tax_amount);
    }

    /**
     * sinkronisasi pesanan batch menghitung pajak EXCLUSIVE secara dinamis dan menambahkan ke final_amount
     */
    public function test_sync_orders_batch_calculates_tax_exclusive_accurately(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        // Konfigurasi pajak cabang: 12% EXCLUSIVE (Pajak Ditambahkan)
        BranchSetting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'branch_id' => $branch->id],
            [
                'tax_enabled' => true,
                'tax_name' => 'PPN (12%)',
                'tax_rate' => 12.00,
                'tax_type' => 'EXCLUSIVE',
            ]
        );

        $product = Product::where('workspace_id', $workspace->id)->firstOrFail();
        $product->update(['base_price' => 100000]);
        $clientOrderId2 = (string) \Illuminate\Support\Str::uuid();

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/orders/sync-batch', [
                'orders' => [
                    [
                        'client_order_id' => $clientOrderId2,
                        'order_number' => 'ORD-TAX-002',
                        'total_amount' => 100000,
                        'discount_amount' => 0,
                        'final_amount' => 112000,
                        'payment_method' => 'QRIS',
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'quantity' => 1,
                                'unit_price' => 100000,
                                'subtotal' => 100000,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $order = Order::withoutGlobalScopes()->where('client_order_id', $clientOrderId2)->firstOrFail();

        // Pajak Exclusive: Subtotal 100.000 + Tax 12.000 = Final amount 112.000
        $this->assertEquals(112000.0, (float) $order->final_amount);
        $this->assertEquals(12.0, (float) $order->tax_rate);
        $this->assertEquals('EXCLUSIVE', $order->tax_type);
        $this->assertEquals(12000.0, (float) $order->tax_amount);
    }

    /**
     * manajer non-owner dilarang memperbarui konfigurasi pajak cabang lain
     */
    public function test_non_owner_cannot_modify_tax_settings_of_another_branch(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branches = Branch::where('workspace_id', $workspace->id)->get();
        $branch1 = $branches[0];

        $branch2 = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Malioboro',
            'lat' => -7.790000,
            'lng' => 110.360000,
            'radius_meters' => 100,
        ]);

        $managerUser = User::create([
            'name' => 'Manager Seturan',
            'email' => 'mgr.seturan2@gmail.com',
            'password' => Hash::make('123456'),
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $managerUser->id,
            'role' => 'ADMIN',
            'branch_id' => $branch1->id,
        ]);

        // Manager cabang 1 mencoba mengubah tax setting cabang 2 -> 403 Forbidden
        $response = $this->actingAs($managerUser)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson("/api/v1/branches/{$branch2->id}", [
                'tax_enabled' => true,
                'tax_rate' => 15.00,
            ]);

        $response->assertStatus(403);
    }
}
