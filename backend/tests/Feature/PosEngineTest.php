<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosEngineTest extends TestCase
{
    use RefreshDatabase;

    private string $deviceToken = 'mock-pos-token-sleman-test';
    private PosTerminal $terminal;
    private Workspace $workspace;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->where('name', 'like', '%Seturan%')->firstOrFail();

        $this->terminal = PosTerminal::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'terminal_name' => 'Tablet Kasir Utama Uji',
            'device_token_hash' => hash('sha256', $this->deviceToken),
            'is_active' => true,
        ]);
    }

    public function test_pos_terminal_can_download_products_catalog(): void
    {
        $category = Category::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Kopi Spesialti',
        ]);

        Product::create([
            'workspace_id' => $this->workspace->id,
            'category_id' => $category->id,
            'name' => 'Caramel Macchiato Test',
            'base_price' => 28000.00,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->getJson('/api/v1/pos/products');

        $response->assertOk()
            ->assertJsonPath('message', 'Katalog produk POS berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'products' => [
                            '*' => [
                                'id',
                                'category_id',
                                'name',
                                'base_price',
                                'is_active',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_cashier_can_open_pos_session(): void
    {
        /** @var User $cashier */
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        $response = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'opening_cash' => 200000,
                'notes' => 'Sesi pagi kasir 1',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Sesi kasir berhasil dibuka.')
            ->assertJsonPath('data.opening_cash', 200000)
            ->assertJsonPath('data.status', 'OPEN');

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $response->json('data.id'),
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'opened_by_user_id' => $cashier->id,
            'status' => 'OPEN',
        ]);
    }

    public function test_opening_second_session_while_active_is_rejected(): void
    {
        /** @var User $cashier */
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        // buka sesi pertama
        $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'opening_cash' => 200000,
            ])->assertStatus(201);

        // buka sesi kedua di cabang yang sama harus ditolak
        $response = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'opening_cash' => 150000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cashier_can_close_session_and_calculate_cash_reconciliation(): void
    {
        /** @var User $cashier */
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        // 1. buka sesi kasir dengan modal 200.000
        $openResponse = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'opening_cash' => 200000,
            ]);
        $sessionId = $openResponse->json('data.id');

        // 2. sinkronkan order tunai senilai 50.000
        $clientOrderId = (string) Str::uuid();
        $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', [
                'orders' => [
                    [
                        'client_order_id' => $clientOrderId,
                        'order_number' => 'ORD-20260823-001',
                        'pos_session_id' => $sessionId,
                        'cashier_user_id' => $cashier->id,
                        'total_amount' => 50000,
                        'discount_amount' => 0,
                        'final_amount' => 50000,
                        'payment_method' => 'CASH',
                        'payment_status' => 'PAID',
                        'items' => [
                            [
                                'product_name' => 'Kopi Susu Gula Aren',
                                'unit_price' => 25000,
                                'quantity' => 2,
                                'subtotal' => 50000,
                            ],
                        ],
                    ],
                ],
            ])->assertOk();

        // 3. tutup sesi kasir dengan uang fisik aktual 245.000 (selisih minus 5.000 dari expected 250.000)
        $closeResponse = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $sessionId,
                'closing_cash_actual' => 245000,
                'notes' => 'selisih uang kembalian 5000',
            ]);

        $closeResponse->assertOk()
            ->assertJsonPath('message', 'Sesi kasir berhasil ditutup dan direkonsiliasi.')
            ->assertJsonPath('data.opening_cash', 200000)
            ->assertJsonPath('data.closing_cash_expected', 250000)
            ->assertJsonPath('data.closing_cash_actual', 245000)
            ->assertJsonPath('data.discrepancy_amount', -5000)
            ->assertJsonPath('data.status', 'CLOSED');

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $sessionId,
            'status' => 'CLOSED',
            'closing_cash_expected' => 250000.00,
            'closing_cash_actual' => 245000.00,
            'discrepancy_amount' => -5000.00,
        ]);
    }

    public function test_pos_terminal_can_sync_batch_orders_offline(): void
    {
        $clientOrderId1 = (string) Str::uuid();
        $clientOrderId2 = (string) Str::uuid();

        $response = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', [
                'orders' => [
                    [
                        'client_order_id' => $clientOrderId1,
                        'order_number' => 'ORD-001',
                        'total_amount' => 30000,
                        'final_amount' => 30000,
                        'payment_method' => 'QRIS',
                        'items' => [
                            [
                                'product_name' => 'Americano',
                                'unit_price' => 30000,
                                'quantity' => 1,
                                'subtotal' => 30000,
                            ],
                        ],
                    ],
                    [
                        'client_order_id' => $clientOrderId2,
                        'order_number' => 'ORD-002',
                        'total_amount' => 45000,
                        'final_amount' => 45000,
                        'payment_method' => 'TRANSFER',
                        'items' => [
                            [
                                'product_name' => 'Croissant',
                                'unit_price' => 22500,
                                'quantity' => 2,
                                'subtotal' => 45000,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Sinkronisasi transaksi pesanan POS berhasil diproses.')
            ->assertJsonPath('data.synced_count', 2);

        $this->assertDatabaseHas('orders', ['client_order_id' => $clientOrderId1]);
        $this->assertDatabaseHas('orders', ['client_order_id' => $clientOrderId2]);
    }

    public function test_pos_order_sync_is_idempotent(): void
    {
        $clientOrderId = (string) Str::uuid();
        $payload = [
            'orders' => [
                [
                    'client_order_id' => $clientOrderId,
                    'order_number' => 'ORD-IDEMPOTENT-001',
                    'total_amount' => 25000,
                    'final_amount' => 25000,
                    'payment_method' => 'CASH',
                    'items' => [
                        [
                            'product_name' => 'Latte',
                            'unit_price' => 25000,
                            'quantity' => 1,
                            'subtotal' => 25000,
                        ],
                    ],
                ],
            ],
        ];

        // sinkronisasi pertama
        $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $payload)
            ->assertOk()
            ->assertJsonPath('data.synced_count', 1);

        // sinkronisasi kedua dengan client_order_id yang sama (simulasi retry jaringan offline)
        $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $payload)
            ->assertOk()
            ->assertJsonPath('data.synced_count', 1);

        // pastikan hanya ada 1 record di database
        $this->assertDatabaseCount('orders', 1);
    }
}
