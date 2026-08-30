<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OutletPurchase;
use App\Models\PosSession;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OutletPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * owner dapat mencatat belanja operasional outlet via web portal
     */
    public function test_owner_can_create_and_list_outlet_purchases_via_web_portal(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $response = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/outlet-purchases', [
                'branch_id' => $branch->id,
                'item_name' => 'Es Batu Kristal 10kg',
                'unit' => 'Pack',
                'quantity' => 3,
                'unit_price' => 12000,
                'total_price' => 36000,
                'category' => 'BAHAN_BAKU_DARURAT',
                'funding_source' => 'CASH_DRAWER',
                'notes' => 'Beli di agen es sebelah',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Belanja outlet berhasil dicatat.',
                'data' => [
                    'item_name' => 'Es Batu Kristal 10kg',
                    'total_price' => '36000.00',
                    'funding_source' => 'CASH_DRAWER',
                ],
            ]);

        $this->assertDatabaseHas('outlet_purchases', [
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'item_name' => 'Es Batu Kristal 10kg',
            'total_price' => 36000,
        ]);

        $listRes = $this->actingAs($owner)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/outlet-purchases');

        $listRes->assertStatus(200)
            ->assertJsonFragment(['item_name' => 'Es Batu Kristal 10kg']);
    }

    /**
     * kasir pada perangkat POS dapat mencatat pengeluaran kas laci dan mempengaruhi rekonsiliasi sesi
     */
    public function test_pos_device_can_record_cash_drawer_purchase_and_deduct_from_closing_cash_expected(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        // 1. Buka sesi kasir dengan modal awal 200.000
        $session = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'opened_by_user_id' => $cashier->id,
            'opening_cash' => 200000,
            'status' => 'OPEN',
            'opened_at' => Carbon::now(),
        ]);

        // 2. Transaksi penjualan kas tunai 100.000
        Order::create([
            'client_order_id' => 'ord-cash-sale-01',
            'order_number' => 'ORD-001',
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'pos_session_id' => $session->id,
            'cashier_user_id' => $cashier->id,
            'total_amount' => 100000,
            'discount_amount' => 0,
            'final_amount' => 100000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        // 3. Catat belanja darurat kas laci (CASH_DRAWER) Rp 25.000 via POS
        $purchaseRes = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/purchases', [
                'pos_session_id' => $session->id,
                'cashier_user_id' => $cashier->id,
                'item_name' => 'Susu Segar Darurat 2L',
                'unit' => 'Liter',
                'quantity' => 2,
                'unit_price' => 12500,
                'total_price' => 25000,
                'category' => 'BAHAN_BAKU_DARURAT',
                'funding_source' => 'CASH_DRAWER',
                'notes' => 'Beli di minimarket',
            ]);

        $purchaseRes->assertStatus(201)
            ->assertJson([
                'message' => 'Pengeluaran belanja outlet berhasil dicatat.',
                'data' => [
                    'item_name' => 'Susu Segar Darurat 2L',
                    'total_price' => '25000.00',
                ],
            ]);

        // 4. Catat belanja eksternal (EXTERNAL_REIMBURSE) Rp 15.000 (tidak memotong kas laci saat ini)
        $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/purchases', [
                'pos_session_id' => $session->id,
                'cashier_user_id' => $cashier->id,
                'item_name' => 'Kabel Colokan Tambahan',
                'unit' => 'Pcs',
                'quantity' => 1,
                'unit_price' => 15000,
                'total_price' => 15000,
                'category' => 'OPERASIONAL_TOKO',
                'funding_source' => 'EXTERNAL_REIMBURSE',
            ])->assertStatus(201);

        // 5. Tutup sesi kasir:
        // Expected Cash = Modal Awal (200.000) + Penjualan Tunai (100.000) - Belanja Laci (25.000) = 275.000
        $closeRes = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $session->id,
                'closing_cash_actual' => 275000,
            ]);

        $closeRes->assertStatus(200)
            ->assertJson([
                'message' => 'Sesi kasir berhasil ditutup dan direkonsiliasi.',
                'data' => [
                    'opening_cash' => 200000.0,
                    'closing_cash_expected' => 275000.0,
                    'closing_cash_actual' => 275000.0,
                    'discrepancy_amount' => 0.0,
                    'status' => 'CLOSED',
                ],
            ]);

        $session->refresh();
        $this->assertEquals(275000.0, (float) $session->closing_cash_expected);
        $this->assertEquals(0.0, (float) $session->discrepancy_amount);
    }

    /**
     * admin cabang non owner tidak dapat mengakses atau menghapus belanja dari cabang lain
     */
    public function test_non_owner_admin_is_strictly_scoped_to_own_branch(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branches = Branch::where('workspace_id', $workspace->id)->get();

        $this->assertGreaterThanOrEqual(1, $branches->count());
        $branch1 = $branches[0];

        // Buat cabang kedua jika belum ada
        $branch2 = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Jakal KM 12',
            'lat' => -7.770000,
            'lng' => 110.370000,
            'radius_meters' => 100,
        ]);

        // Buat user manager untuk branch 1
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

        // Buat catatan belanja di branch 2
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $purchaseBranch2 = OutletPurchase::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch2->id,
            'item_name' => 'Galon Aqua Cabang 2',
            'unit' => 'Galon',
            'quantity' => 2,
            'unit_price' => 20000,
            'total_price' => 40000,
            'category' => 'UTILITAS',
            'funding_source' => 'CASH_DRAWER',
            'recorded_by_user_id' => $owner->id,
        ]);

        // Manager branch 1 mencoba mengakses purchase di branch 2 -> 403 Forbidden
        $showRes = $this->actingAs($managerUser)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson("/api/v1/outlet-purchases/{$purchaseBranch2->id}");

        $showRes->assertStatus(403);

        // Manager branch 1 mencoba menghapus purchase di branch 2 -> 403 Forbidden
        $deleteRes = $this->actingAs($managerUser)
            ->withHeader('X-Workspace-Id', $workspace->id)
            ->deleteJson("/api/v1/outlet-purchases/{$purchaseBranch2->id}");

        $deleteRes->assertStatus(403);
        $this->assertDatabaseHas('outlet_purchases', ['id' => $purchaseBranch2->id]);
    }
}
