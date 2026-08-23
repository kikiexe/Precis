<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\PosTerminal;
use App\Models\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosOfflineSyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;
    private Branch $branch;
    private PosTerminal $terminal;
    private string $deviceToken = 'pos-idempotency-test-token';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->firstOrFail();

        $this->terminal = PosTerminal::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'terminal_name' => 'Tablet POS Idempotency Test',
            'device_token_hash' => hash('sha256', $this->deviceToken),
            'is_active' => true,
        ]);
    }

    public function test_partial_offline_batch_resync_does_not_duplicate_existing_records(): void
    {
        $clientOrderId1 = (string) Str::uuid();
        $clientOrderId2 = (string) Str::uuid();
        $clientOrderId3 = (string) Str::uuid();
        $clientOrderId4 = (string) Str::uuid();

        // batch 1: kirim order 1 dan 2
        $batch1 = [
            'orders' => [
                [
                    'client_order_id' => $clientOrderId1,
                    'order_number' => 'ORD-BATCH1-01',
                    'total_amount' => 30000,
                    'final_amount' => 30000,
                    'payment_method' => 'CASH',
                    'items' => [
                        [
                            'product_name' => 'Cappuccino',
                            'unit_price' => 30000,
                            'quantity' => 1,
                            'subtotal' => 30000,
                        ],
                    ],
                ],
                [
                    'client_order_id' => $clientOrderId2,
                    'order_number' => 'ORD-BATCH1-02',
                    'total_amount' => 50000,
                    'final_amount' => 50000,
                    'payment_method' => 'QRIS',
                    'items' => [
                        [
                            'product_name' => 'Matcha Latte',
                            'unit_price' => 25000,
                            'quantity' => 2,
                            'subtotal' => 50000,
                        ],
                    ],
                ],
            ],
        ];

        $response1 = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $batch1);

        $response1->assertOk()
            ->assertJsonPath('data.synced_count', 2);

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 2);

        // batch 2 (partial repeat): mengirim ulang order 1 dan 2 beserta order baru 3 dan 4
        $batch2 = [
            'orders' => [
                $batch1['orders'][0], // order 1 (duplikat)
                $batch1['orders'][1], // order 2 (duplikat)
                [
                    'client_order_id' => $clientOrderId3,
                    'order_number' => 'ORD-BATCH2-03',
                    'total_amount' => 20000,
                    'final_amount' => 20000,
                    'payment_method' => 'CASH',
                    'items' => [
                        [
                            'product_name' => 'Espresso Single',
                            'unit_price' => 20000,
                            'quantity' => 1,
                            'subtotal' => 20000,
                        ],
                    ],
                ],
                [
                    'client_order_id' => $clientOrderId4,
                    'order_number' => 'ORD-BATCH2-04',
                    'total_amount' => 40000,
                    'final_amount' => 40000,
                    'payment_method' => 'TRANSFER',
                    'items' => [
                        [
                            'product_name' => 'Croissant Almond',
                            'unit_price' => 40000,
                            'quantity' => 1,
                            'subtotal' => 40000,
                        ],
                    ],
                ],
            ],
        ];

        $response2 = $this->withHeader('X-Device-Token', $this->deviceToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $batch2);

        $response2->assertOk()
            ->assertJsonPath('data.synced_count', 4);

        // pastikan total order di database tepat 4 (tidak ada duplikasi order 1 dan 2)
        $this->assertDatabaseCount('orders', 4);
        $this->assertDatabaseCount('order_items', 4);

        // pastikan total nominal transaksi di database tepat
        $totalSales = (float) Order::withoutGlobalScopes()
            ->where('workspace_id', $this->workspace->id)
            ->sum('final_amount');

        $this->assertEquals(140000.00, $totalSales);
    }
}
