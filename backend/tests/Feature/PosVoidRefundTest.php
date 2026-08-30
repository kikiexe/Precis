<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Models\WorkspaceRolePermission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosVoidRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * helper untuk setup terminal, session, kasir, dan approver
     *
     * @return array{workspace: Workspace, branch: Branch, terminal: PosTerminal, cashier: User, manager: User, rawToken: string, session: PosSession}
     */
    private function setupPosContext(): array
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $terminal = PosTerminal::withoutGlobalScopes()->where('branch_id', $branch->id)->firstOrFail();
        $rawToken = 'pos-device-token-seturan-01';

        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        $manager = User::create([
            'name' => 'Store Manager Budi',
            'email' => 'manager_budi@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $manager->id,
            'branch_id' => $branch->id,
            'role' => 'MANAGER',
            'job_title' => 'Store Manager',
            'pin' => Hash::make('888999'),
            'base_salary' => 6000000,
            'is_active' => true,
        ]);

        $session = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branch->id,
            'opened_by_user_id' => $cashier->id,
            'opening_cash' => 200000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        return [
            'workspace' => $workspace,
            'branch' => $branch,
            'terminal' => $terminal,
            'cashier' => $cashier,
            'manager' => $manager,
            'rawToken' => $rawToken,
            'session' => $session,
        ];
    }

    /**
     * void order berhasil mengubah status ke VOIDED dan tidak terhitung dalam cash sales saat tutup kas
     */
    public function test_void_order_changes_status_to_voided_and_excludes_from_cash_sales(): void
    {
        $ctx = $this->setupPosContext();

        // 1. Buat order tunai di sesi aktif
        $order = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'pos_terminal_id' => $ctx['terminal']->id,
            'cashier_user_id' => $ctx['cashier']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-VOID-01',
            'total_amount' => 50000,
            'discount_amount' => 0,
            'final_amount' => 50000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        // 2. Eksekusi void dengan PIN manager yang sah
        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$order->id}/void", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Salah input menu pesanan pelanggan',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transaksi pesanan berhasil dibatalkan (void).',
                'data' => [
                    'id' => $order->id,
                    'payment_status' => 'VOIDED',
                    'void_reason' => 'Salah input menu pesanan pelanggan',
                    'voided_by_user_id' => $ctx['manager']->id,
                ],
            ]);

        $order->refresh();
        $this->assertEquals('VOIDED', $order->payment_status);
        $this->assertEquals('Salah input menu pesanan pelanggan', $order->void_reason);
        $this->assertNotNull($order->voided_at);

        // 3. Verifikasi tutup sesi kasir -> expected cash harus tetap 200.000 (tidak ketambahan 50.000 karena di-void)
        $closeResponse = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $ctx['session']->id,
                'closing_cash_actual' => 200000,
            ]);

        $closeResponse->assertStatus(200);
        $this->assertEquals(200000.0, (float) $closeResponse->json('data.closing_cash_expected'));
        $this->assertEquals(0.0, (float) $closeResponse->json('data.discrepancy_amount'));
    }

    /**
     * void order ditolak jika PIN approver salah
     */
    public function test_void_order_fails_if_approver_pin_is_wrong(): void
    {
        $ctx = $this->setupPosContext();

        $order = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-VOID-02',
            'total_amount' => 45000,
            'final_amount' => 45000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$order->id}/void", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '000000', // PIN salah
                'reason' => 'Salah input',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);
    }

    /**
     * void order ditolak jika approver tidak memiliki izin pos.void_order
     */
    public function test_void_order_fails_if_approver_lacks_permission(): void
    {
        $ctx = $this->setupPosContext();

        $staffUser = User::create([
            'name' => 'Barista Biasa',
            'email' => 'barista_biasa@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $ctx['workspace']->id,
            'user_id' => $staffUser->id,
            'branch_id' => $ctx['branch']->id,
            'role' => 'STAFF',
            'job_title' => 'Barista',
            'pin' => Hash::make('123123'),
            'base_salary' => 2500000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-VOID-03',
            'total_amount' => 30000,
            'final_amount' => 30000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$order->id}/void", [
                'approver_user_id' => $staffUser->id,
                'pin' => '123123',
                'reason' => 'Barista mencoba void sendiri',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['approver_user_id']);
    }

    /**
     * void order ditolak jika pesanan bukan berasal dari sesi kasir yang sedang aktif
     */
    public function test_void_order_fails_if_order_is_not_in_active_open_session(): void
    {
        $ctx = $this->setupPosContext();

        // Buat sesi kemarin yang sudah closed
        $oldSession = PosSession::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'opened_by_user_id' => $ctx['cashier']->id,
            'opening_cash' => 100000,
            'status' => 'CLOSED',
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subDay()->addHours(8),
        ]);

        $oldOrder = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $oldSession->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-OLD-01',
            'total_amount' => 35000,
            'final_amount' => 35000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$oldOrder->id}/void", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Coba void transaksi kemarin',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * refund order penuh mengubah status ke REFUNDED dan mencatat refunded_in_session_id
     */
    public function test_refund_order_full_changes_status_to_refunded_and_records_session(): void
    {
        $ctx = $this->setupPosContext();

        $oldSession = PosSession::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'opened_by_user_id' => $ctx['cashier']->id,
            'opening_cash' => 100000,
            'status' => 'CLOSED',
            'opened_at' => now()->subDay(),
        ]);

        $oldOrder = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $oldSession->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-REFUND-01',
            'total_amount' => 75000,
            'final_amount' => 75000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$oldOrder->id}/refund", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Komplain rasa kopi terlalu asam',
                'refund_method' => 'CASH_DRAWER',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pengembalian dana (refund) transaksi berhasil diproses.',
                'data' => [
                    'id' => $oldOrder->id,
                    'payment_status' => 'REFUNDED',
                    'refund_amount' => 75000.0,
                    'refund_reason' => 'Komplain rasa kopi terlalu asam',
                    'refund_method' => 'CASH_DRAWER',
                    'refunded_in_session_id' => $ctx['session']->id,
                ],
            ]);

        $oldOrder->refresh();
        $this->assertEquals('REFUNDED', $oldOrder->payment_status);
        $this->assertEquals(75000.0, (float) $oldOrder->refund_amount);
        $this->assertEquals($ctx['session']->id, $oldOrder->refunded_in_session_id);
    }

    /**
     * refund parsial mengubah status ke PARTIALLY_REFUNDED
     */
    public function test_refund_order_partial_changes_status_to_partially_refunded(): void
    {
        $ctx = $this->setupPosContext();

        $order = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-REFUND-02',
            'total_amount' => 100000,
            'final_amount' => 100000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$order->id}/refund", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Satu croissant gosong',
                'refund_amount' => 30000,
                'refund_method' => 'CASH_DRAWER',
            ]);

        $response->assertStatus(200);
        $order->refresh();

        $this->assertEquals('PARTIALLY_REFUNDED', $order->payment_status);
        $this->assertEquals(30000.0, (float) $order->refund_amount);
    }

    /**
     * refund tunai lintas sesi memotong expected cash laci pada sesi aktif yang mengeksekusi refund
     */
    public function test_cash_refund_deducts_expected_cash_during_close_session(): void
    {
        $ctx = $this->setupPosContext();

        // 1. Sesi aktif dibuka dengan modal Rp 200.000
        // 2. Ada penjualan tunai hari ini Rp 100.000
        Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TODAY-01',
            'total_amount' => 100000,
            'final_amount' => 100000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        // 3. Ada order kemarin (sesi kemarin) yang di-refund tunai Rp 40.000 dari laci hari ini
        $yesterdayOrder = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => null, // sesi kemarin
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-YESTERDAY-01',
            'total_amount' => 40000,
            'final_amount' => 40000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$yesterdayOrder->id}/refund", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Refund tunai kemarin',
                'refund_amount' => 40000,
                'refund_method' => 'CASH_DRAWER',
            ])
            ->assertStatus(200);

        // 4. Tutup sesi hari ini:
        // Expected Cash = Modal (200.000) + Penjualan (100.000) - Refund Tunai (40.000) = Rp 260.000
        $closeResponse = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $ctx['session']->id,
                'closing_cash_actual' => 260000,
            ]);

        $closeResponse->assertStatus(200);
        $this->assertEquals(260000.0, (float) $closeResponse->json('data.closing_cash_expected'));
        $this->assertEquals(0.0, (float) $closeResponse->json('data.discrepancy_amount'));
    }

    /**
     * refund non-tunai (misal QRIS_TRANSFER) tidak memotong uang kas laci pada close session
     */
    public function test_non_cash_refund_does_not_deduct_cash_drawer_in_close_session(): void
    {
        $ctx = $this->setupPosContext();

        // Penjualan tunai hari ini Rp 100.000
        Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TODAY-02',
            'total_amount' => 100000,
            'final_amount' => 100000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $qrisOrder = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-QRIS-01',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'payment_method' => 'QRIS',
            'payment_status' => 'PAID',
        ]);

        // Refund via QRIS transfer kembali ke bank customer
        $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$qrisOrder->id}/refund", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Refund QRIS via transfer',
                'refund_amount' => 50000,
                'refund_method' => 'QRIS_TRANSFER',
            ])
            ->assertStatus(200);

        // Tutup sesi: Expected Cash = Modal (200.000) + Penjualan (100.000) = Rp 300.000 (tidak dipotong refund QRIS)
        $closeResponse = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $ctx['session']->id,
                'closing_cash_actual' => 300000,
            ]);

        $closeResponse->assertStatus(200);
        $this->assertEquals(300000.0, (float) $closeResponse->json('data.closing_cash_expected'));
    }

    /**
     * refund ditolak jika nominal melebihi sisa yang dapat di-refund
     */
    public function test_refund_cannot_exceed_remaining_final_amount(): void
    {
        $ctx = $this->setupPosContext();

        $order = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-TEST-OVER-REFUND',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        // Request refund Rp 60.000 untuk pesanan senilai Rp 50.000 -> harus ditolak (422)
        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$order->id}/refund", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Minta lebih',
                'refund_amount' => 60000,
                'refund_method' => 'CASH_DRAWER',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refund_amount']);
    }

    /**
     * tidak dapat membatalkan (void) pesanan yang sudah berstatus VOIDED atau REFUNDED
     */
    public function test_cannot_void_already_voided_or_refunded_order(): void
    {
        $ctx = $this->setupPosContext();

        $voidedOrder = Order::create([
            'workspace_id' => $ctx['workspace']->id,
            'branch_id' => $ctx['branch']->id,
            'pos_session_id' => $ctx['session']->id,
            'client_order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-ALREADY-VOIDED',
            'total_amount' => 40000,
            'final_amount' => 40000,
            'payment_method' => 'CASH',
            'payment_status' => 'VOIDED',
        ]);

        $response = $this->withHeader('X-Device-Token', $ctx['rawToken'])
            ->postJson("/api/v1/pos/orders/{$voidedOrder->id}/void", [
                'approver_user_id' => $ctx['manager']->id,
                'pin' => '888999',
                'reason' => 'Void ulang',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
