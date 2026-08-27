<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_token_is_strictly_forbidden_from_superadmin_endpoints(): void
    {
        // Buat Superadmin di DB
        Superadmin::create([
            'name' => 'Root Admin',
            'email' => 'root@precis.com',
            'password' => Hash::make('Secret123!'),
        ]);

        // Buat regular user tenant biasa
        $user = User::create([
            'name' => 'Budi Tenant',
            'email' => 'budi@tenant.com',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        $userToken = $user->createToken('user_token')->plainTextToken;

        // Coba akses metrics superadmin dengan token user biasa
        $response = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->getJson('/api/v1/superadmin/metrics');

        $response->assertStatus(403);

        // Coba akses direktori tenant superadmin dengan token user biasa
        $responseTenants = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->getJson('/api/v1/superadmin/tenants');

        $responseTenants->assertStatus(403);
    }

    public function test_legitimate_superadmin_can_access_superadmin_endpoints(): void
    {
        $superadmin = Superadmin::create([
            'name' => 'Root Admin',
            'email' => 'superadmin@precis.com',
            'password' => Hash::make('SuperSecret123!'),
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/v1/superadmin/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'mrr',
                'arr',
                'total_revenue',
                'tenants' => ['active', 'grace_period', 'suspended', 'trial', 'total'],
            ],
        ]);
    }

    public function test_pos_terminal_cannot_close_session_from_another_branch(): void
    {
        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
            'subscription_status' => 'ACTIVE',
        ]);

        $workspace = Workspace::create([
            'name' => 'Cafe Test',
            'slug' => 'cafe-test',
            'owner_user_id' => $user->id,
        ]);

        $branchA = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Cabang A',
            'lat' => -7.78,
            'lng' => 110.36,
            'radius_meters' => 50,
        ]);

        $branchB = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Cabang B',
            'lat' => -7.79,
            'lng' => 110.37,
            'radius_meters' => 50,
        ]);

        $rawTokenA = 'device_token_branch_a';
        $terminalA = PosTerminal::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchA->id,
            'terminal_name' => 'POS Branch A',
            'device_token_hash' => hash('sha256', $rawTokenA),
            'is_active' => true,
        ]);

        // Buat sesi kasir di Cabang B
        $sessionBranchB = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchB->id,
            'opened_by_user_id' => $user->id,
            'opening_cash' => 100000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        // Terminal Cabang A mencoba menutup sesi milik Cabang B
        $response = $this->withHeader('X-Device-Token', $rawTokenA)
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $sessionBranchB->id,
                'closing_cash_actual' => 100000,
            ]);

        $response->assertStatus(422);
    }

    public function test_pos_sync_batch_recalculates_prices_from_server_database(): void
    {
        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner2@test.com',
            'password' => Hash::make('password'),
            'subscription_status' => 'ACTIVE',
        ]);

        $workspace = Workspace::create([
            'name' => 'Resto Test',
            'slug' => 'resto-test',
            'owner_user_id' => $user->id,
        ]);

        $branch = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Cabang Utama',
            'lat' => -7.78,
            'lng' => 110.36,
            'radius_meters' => 50,
        ]);

        $category = Category::create([
            'workspace_id' => $workspace->id,
            'name' => 'Coffee',
        ]);

        // Produk di database berharga Rp 35.000
        $product = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Espresso Double',
            'base_price' => 35000.00,
            'is_active' => true,
        ]);

        $rawToken = 'device_token_pos_test';
        PosTerminal::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'POS 1',
            'device_token_hash' => hash('sha256', $rawToken),
            'is_active' => true,
        ]);

        // Client mencoba mengirimkan harga palsu Rp 1.000 per cup untuk 2 cup
        $payload = [
            'orders' => [
                [
                    'client_order_id' => (string) Str::uuid(),
                    'order_number' => 'ORD-SPOOF-001',
                    'total_amount' => 2000.00,
                    'discount_amount' => 0.00,
                    'final_amount' => 2000.00,
                    'payment_method' => 'CASH',
                    'payment_status' => 'PAID',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'product_name' => 'Espresso Double',
                            'unit_price' => 1000.00,
                            'quantity' => 2,
                            'subtotal' => 2000.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $payload);

        $response->assertStatus(200);

        // Verifikasi di database: harga tersimpan wajib Rp 35.000 x 2 = Rp 70.000
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'unit_price' => 35000.00,
            'quantity' => 2,
            'subtotal' => 70000.00,
        ]);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-SPOOF-001',
            'total_amount' => 70000.00,
            'final_amount' => 70000.00,
        ]);
    }
}
