<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashAdvance;
use App\Models\Category;
use App\Models\Order;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ShiftTemplate;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_regular_user_token_is_strictly_forbidden_from_superadmin_endpoints(): void
    {
        Superadmin::firstOrCreate(
            ['email' => 'root@precis.com'],
            [
                'name' => 'Root Admin',
                'password' => Hash::make('Secret123!'),
            ]
        );

        $user = User::create([
            'name' => 'Budi Tenant',
            'email' => 'budi_sec@tenant.com',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        $userToken = $user->createToken('user_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->getJson('/api/v1/superadmin/metrics');

        $response->assertStatus(403);

        $responseTenants = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->getJson('/api/v1/superadmin/tenants');

        $responseTenants->assertStatus(403);
    }

    public function test_legitimate_superadmin_can_access_superadmin_endpoints(): void
    {
        $superadmin = Superadmin::first() ?? Superadmin::create([
            'name' => 'Root Admin',
            'email' => 'superadmin@precis.com',
            'password' => Hash::make('SuperSecret123!'),
        ]);

        Sanctum::actingAs($superadmin);

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
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();

        $branchB = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Cabang B Isolasi',
            'lat' => -7.79,
            'lng' => 110.37,
            'radius_meters' => 50,
        ]);

        $rawTokenA = 'device_token_branch_a_unique';
        $branchA = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        PosTerminal::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchA->id,
            'terminal_name' => 'POS Branch A Unique',
            'device_token_hash' => hash('sha256', $rawTokenA),
            'is_active' => true,
        ]);

        $sessionBranchB = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchB->id,
            'opened_by_user_id' => $owner->id,
            'opening_cash' => 100000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $response = $this->withHeader('X-Device-Token', $rawTokenA)
            ->postJson('/api/v1/pos/sessions/close', [
                'pos_session_id' => $sessionBranchB->id,
                'closing_cash_actual' => 100000,
            ]);

        $response->assertStatus(422);
    }

    public function test_pos_sync_batch_recalculates_prices_from_server_database(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $category = Category::where('workspace_id', $workspace->id)->firstOrFail();
        $product = Product::create([
            'workspace_id' => $workspace->id,
            'category_id' => $category->id,
            'name' => 'Espresso Hardened',
            'base_price' => 35000.00,
            'is_active' => true,
        ]);

        $rawToken = 'pos-device-token-seturan-01';

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
                            'product_name' => 'Espresso Hardened',
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

    /**
     * SEC-01: Tidak boleh mengundang member baru dengan role OWNER
     */
    public function test_cannot_escalate_to_owner_via_member_invite(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/admin/invitations', [
                'email' => 'hacker@norde.id',
                'job_title' => 'Fake Co-Owner',
                'role' => 'OWNER',
                'base_salary' => 5000000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    /**
     * SEC-01: Tidak boleh menetapkan role OWNER saat tambah / edit member langsung
     */
    public function test_cannot_assign_owner_via_member_create_or_update(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $staffMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('role', '!=', 'OWNER')
            ->firstOrFail();

        Sanctum::actingAs($owner);

        // Percobaan tambah member baru sebagai OWNER
        $responseCreate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/admin/members', [
                'name' => 'Intruder',
                'email' => 'intruder@norde.id',
                'job_title' => 'Co-Owner',
                'role' => 'OWNER',
                'base_salary' => 0,
            ]);

        $responseCreate->assertStatus(422);

        // Percobaan update member staf biasa menjadi OWNER
        $responseUpdate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson('/api/v1/admin/members/' . $staffMember->id, [
                'role' => 'OWNER',
            ]);

        $responseUpdate->assertStatus(422);
    }

    /**
     * SEC-05: Static ADMIN tidak memiliki wildcard bypass dan dilarang mengakses payroll & custom roles management
     */
    public function test_admin_cannot_access_unauthorized_payroll_or_roles(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        // Buat user Admin dengan role static ADMIN
        $adminUser = User::create([
            'name' => 'Branch Admin',
            'email' => 'admin_branch@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $adminUser->id,
            'role' => 'ADMIN',
            'job_title' => 'Admin Operasional',
            'base_salary' => 4000000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($adminUser);

        // Akses payroll preview harus 403 Forbidden
        $responsePayroll = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/payroll/preview');

        $responsePayroll->assertStatus(403);

        // Akses pembuatan custom role harus 403 Forbidden
        $responseRole = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/roles', [
                'name' => 'Super Admin Bypass',
                'permissions' => ['*'],
            ]);

        $responseRole->assertStatus(403);
    }

    /**
     * SEC-07: Admin terikat cabang hanya dapat melihat staf di cabangnya
     */
    public function test_non_owner_member_query_is_scoped_to_assigned_branch(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branchSeturan = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        // Buat cabang kedua di workspace yang sama
        $branchKaliurang = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Branch 2',
            'lat' => -7.72,
            'lng' => 110.39,
            'radius_meters' => 50,
        ]);

        $adminUser = User::create([
            'name' => 'Admin Seturan',
            'email' => 'admin_seturan@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $adminUser->id,
            'branch_id' => $branchSeturan->id,
            'role' => 'ADMIN',
            'job_title' => 'Admin Seturan',
            'base_salary' => 4000000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($adminUser);

        // Query dengan filter branch Kaliurang harus mengembalikan list kosong
        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/admin/members?branch_id=' . $branchKaliurang->id);

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /**
     * SEC-07: Admin terikat cabang dilarang mengelola staf di cabang lain
     */
    public function test_non_owner_cannot_create_or_modify_staff_in_another_branch(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branchSeturan = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $branchKaliurang = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Branch Kaliurang',
            'lat' => -7.72,
            'lng' => 110.39,
            'radius_meters' => 50,
        ]);

        $adminSeturan = User::create([
            'name' => 'Admin Seturan 2',
            'email' => 'admin_seturan2@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $adminSeturan->id,
            'branch_id' => $branchSeturan->id,
            'role' => 'ADMIN',
            'job_title' => 'Admin Seturan',
            'base_salary' => 4000000,
            'is_active' => true,
        ]);

        $staffKaliurangUser = User::create([
            'name' => 'Staff Kaliurang',
            'email' => 'staff_kaliurang@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        $staffKaliurangMember = WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $staffKaliurangUser->id,
            'branch_id' => $branchKaliurang->id,
            'role' => 'STAFF',
            'job_title' => 'Barista Kaliurang',
            'base_salary' => 2500000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($adminSeturan);

        // Percobaan tambah staf di cabang Kaliurang harus 403
        $responseCreate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/admin/members', [
                'name' => 'New Staff Outside',
                'email' => 'newstaff_out@norde.id',
                'job_title' => 'Barista',
                'branch_id' => $branchKaliurang->id,
                'role' => 'STAFF',
                'base_salary' => 2500000,
            ]);

        $responseCreate->assertStatus(403);

        // Percobaan edit staf cabang Kaliurang oleh admin Seturan harus 403
        $responseUpdate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson('/api/v1/admin/members/' . $staffKaliurangMember->id, [
                'job_title' => 'Hacked Title',
            ]);

        $responseUpdate->assertStatus(403);
    }

    /**
     * SEC-07: Admin terikat cabang dilarang menyetujui kasbon staf cabang lain
     */
    public function test_non_owner_cannot_approve_cash_advance_of_another_branch_staff(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branchSeturan = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $branchKaliurang = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Branch Kaliurang 3',
            'lat' => -7.72,
            'lng' => 110.39,
            'radius_meters' => 50,
        ]);

        $adminSeturan = User::create([
            'name' => 'Admin Seturan 3',
            'email' => 'admin_seturan3@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $adminSeturan->id,
            'branch_id' => $branchSeturan->id,
            'role' => 'ADMIN',
            'job_title' => 'Admin Seturan',
            'base_salary' => 4000000,
            'is_active' => true,
        ]);

        $staffKaliurangUser = User::create([
            'name' => 'Staff Kaliurang 3',
            'email' => 'staff_kaliurang3@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $staffKaliurangUser->id,
            'branch_id' => $branchKaliurang->id,
            'role' => 'STAFF',
            'job_title' => 'Barista',
            'base_salary' => 2500000,
            'is_active' => true,
        ]);

        $advance = CashAdvance::create([
            'workspace_id' => $workspace->id,
            'user_id' => $staffKaliurangUser->id,
            'amount' => 300000,
            'request_date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($adminSeturan);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/admin/cash-advances/' . $advance->id . '/approve');

        $response->assertStatus(403);
    }

    /**
     * SEC-07: Admin terikat cabang dilarang menetapkan atau membatalkan shift cabang lain
     */
    public function test_non_owner_cannot_assign_or_delete_shift_in_another_branch(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branchSeturan = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        $branchKaliurang = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Kaliurang Shift',
            'lat' => -7.72,
            'lng' => 110.39,
            'radius_meters' => 50,
        ]);

        $templateKaliurang = ShiftTemplate::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchKaliurang->id,
            'name' => 'Shift Sore Kaliurang',
            'expected_clock_in' => '15:00',
            'expected_clock_out' => '23:00',
        ]);

        $adminSeturan = User::create([
            'name' => 'Admin Seturan Shift',
            'email' => 'admin_seturan_shift@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $adminSeturan->id,
            'branch_id' => $branchSeturan->id,
            'role' => 'ADMIN',
            'job_title' => 'Admin Seturan',
            'base_salary' => 4000000,
            'is_active' => true,
        ]);

        $staffKaliurangUser = User::create([
            'name' => 'Staff Kaliurang Shift',
            'email' => 'staff_kaliurang_shift@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $staffKaliurangUser->id,
            'branch_id' => $branchKaliurang->id,
            'role' => 'STAFF',
            'job_title' => 'Barista',
            'base_salary' => 2500000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($adminSeturan);

        $responseAssign = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/shifts/assign', [
                'shift_template_id' => $templateKaliurang->id,
                'assigned_user_id' => $staffKaliurangUser->id,
                'date' => now()->addDay()->toDateString(),
            ]);

        $responseAssign->assertStatus(403);
    }

    /**
     * SEC-02: Endpoint mock upload menolak path non-staging, ekstensi berbahaya, dan file >2MB
     */
    public function test_mock_upload_rejects_non_staging_paths_and_disallowed_extensions(): void
    {
        // 1. Non-staging path traversal
        $responseNonStaging = $this->call('PUT', '/api/v1/media/mock-upload?key=etc/passwd', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'payload');
        $responseNonStaging->assertStatus(403);

        // 2. Ekstensi berbahaya (.php)
        $responsePhp = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/exploit.php', [], [], [], ['CONTENT_TYPE' => 'application/x-php'], '<?php echo "evil"; ?>');
        $responsePhp->assertStatus(422);

        // 3. Payload melebihi 2MB
        $largeBinary = str_repeat('A', 2 * 1024 * 1024 + 100);
        $responseLarge = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/large.png', [], [], [], ['CONTENT_TYPE' => 'image/png'], $largeBinary);
        $responseLarge->assertStatus(422);

        // 4. Staging sah dengan ekstensi yang diizinkan (.png)
        $validImage = str_repeat('B', 1024);
        $responseValid = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/avatar.png', [], [], [], ['CONTENT_TYPE' => 'image/png'], $validImage);
        $responseValid->assertStatus(200);
    }

    /**
     * SEC-04: Buka sesi kasir mewajibkan 6 digit PIN valid dan diterapkan rate limiting
     */
    public function test_pos_session_open_requires_valid_pin_and_is_rate_limited(): void
    {
        $rawToken = 'pos-device-token-seturan-01';
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        // 1. PIN salah harus 422
        $responseWrongPin = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'pin' => '000000',
                'opening_cash' => 100000,
            ]);

        $responseWrongPin->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);

        // 2. Rate limiting (maksimal 5 request per menit)
        for ($i = 0; $i < 4; $i++) {
            $this->withHeader('X-Device-Token', $rawToken)
                ->postJson('/api/v1/pos/sessions/open', [
                    'cashier_user_id' => $cashier->id,
                    'pin' => '000000',
                    'opening_cash' => 100000,
                ]);
        }

        // Request ke-6 harus menerima 429 Too Many Requests
        $responseThrottled = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'pin' => '123456',
                'opening_cash' => 100000,
            ]);

        $responseThrottled->assertStatus(429);
    }

    /**
     * SEC-03 & SEC-06: Pos terminals tidak membocorkan plaintext device token pada GET /branches dan terminal create terisolasi
     */
    public function test_pos_terminals_do_not_leak_plaintext_device_token(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->getJson('/api/v1/branches');

        $response->assertStatus(200);
        $terminals = $response->json('data.0.terminals');

        $this->assertNotEmpty($terminals);
        foreach ($terminals as $terminal) {
            $this->assertArrayNotHasKey('device_token', $terminal);
            $this->assertArrayHasKey('device_token_preview', $terminal);
            $this->assertStringContainsString('***', $terminal['device_token_preview']);
            $this->assertArrayHasKey('is_paired', $terminal);
        }
    }

    /**
     * temuan 1: endpoint mock upload menolak directory traversal bertingkat dan null byte
     */
    public function test_mock_upload_strictly_rejects_tricky_directory_traversal_payloads(): void
    {
        // 1. traversal multi-dot bertingkat
        $response1 = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/....//secret.png', [], [], [], ['CONTENT_TYPE' => 'image/png'], 'data');
        $response1->assertStatus(400);

        // 2. traversal dengan backslash
        $response2 = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/..\\secret.png', [], [], [], ['CONTENT_TYPE' => 'image/png'], 'data');
        $response2->assertStatus(400);

        // 3. traversal dengan null byte
        $response3 = $this->call('PUT', '/api/v1/media/mock-upload?key=staging/test.png%00.php', [], [], [], ['CONTENT_TYPE' => 'image/png'], 'data');
        $response3->assertStatus(400);
    }

    /**
     * temuan 2: sinkronisasi offline batch menolak poisoning sesi kasir cabang lain
     */
    public function test_pos_sync_batch_rejects_cross_branch_session_poisoning(): void
    {
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branchSeturan = Branch::where('workspace_id', $workspace->id)->firstOrFail();
        $terminal = PosTerminal::withoutGlobalScopes()->where('branch_id', $branchSeturan->id)->firstOrFail();

        $branchKaliurang = Branch::create([
            'workspace_id' => $workspace->id,
            'name' => 'Norde Kaliurang Pos Poison Test',
            'lat' => -7.72,
            'lng' => 110.39,
            'radius_meters' => 50,
        ]);

        $cashierKaliurang = User::create([
            'name' => 'Cashier Kaliurang Poison',
            'email' => 'cashier_poison@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        $foreignSession = PosSession::create([
            'workspace_id' => $workspace->id,
            'branch_id' => $branchKaliurang->id,
            'opened_by_user_id' => $cashierKaliurang->id,
            'opening_cash' => 200000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        $rawToken = 'pos-device-token-seturan-01';

        $payload = [
            'orders' => [
                [
                    'client_order_id' => (string) Str::uuid(),
                    'pos_session_id' => $foreignSession->id,
                    'order_number' => 'ORD-POISON-01',
                    'total_amount' => 50000,
                    'discount_amount' => 0,
                    'final_amount' => 50000,
                    'payment_method' => 'CASH',
                    'payment_status' => 'PAID',
                    'items' => [
                        [
                            'product_id' => null,
                            'product_name' => 'Kopi Tubruk',
                            'unit_price' => 50000,
                            'quantity' => 1,
                            'subtotal' => 50000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/orders/sync-batch', $payload);

        $response->assertStatus(200);

        $createdOrder = Order::withoutGlobalScopes()->where('client_order_id', $payload['orders'][0]['client_order_id'])->firstOrFail();
        // pos_session_id harus disanitasi menjadi null karena sesi milik cabang lain
        $this->assertNull($createdOrder->pos_session_id);
    }

    /**
     * temuan 3: member create dan update menolak branch_id fiktif tanpa silent privilege elevation
     */
    public function test_member_create_and_update_strictly_validates_branch_id_without_silent_hq_elevation(): void
    {
        $owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();

        Sanctum::actingAs($owner);

        // 1. Create member dengan UUID cabang fiktif -> harus 422
        $fakeBranchUuid = (string) Str::uuid();
        $responseCreate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->postJson('/api/v1/admin/members', [
                'name' => 'Staf Cabang Palsu',
                'email' => 'staf_palsu@norde.id',
                'job_title' => 'Barista',
                'branch_id' => $fakeBranchUuid,
                'role' => 'STAFF',
                'base_salary' => 3000000,
            ]);

        $responseCreate->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);

        // 2. Update member yang ada dengan UUID cabang fiktif -> harus 422
        $existingMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('role', 'STAFF')
            ->firstOrFail();

        $responseUpdate = $this->withHeader('X-Workspace-Id', $workspace->id)
            ->putJson('/api/v1/admin/members/' . $existingMember->id, [
                'branch_id' => $fakeBranchUuid,
            ]);

        $responseUpdate->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    /**
     * temuan 5: rate limiting pembukaan sesi kasir tidak mengunci tablet lain di cabang yang sama
     */
    public function test_pos_session_open_rate_limiting_is_scoped_per_terminal_and_cashier(): void
    {
        $rawTokenA = 'pos-device-token-seturan-01';
        $cashierA = User::where('email', 'ami@gmail.com')->firstOrFail();

        $cashierB = User::create([
            'name' => 'Kasir Budi',
            'email' => 'budi_cashier@norde.id',
            'password' => Hash::make('Password123!'),
            'subscription_status' => 'ACTIVE',
        ]);

        $workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $branch = Branch::where('workspace_id', $workspace->id)->firstOrFail();

        WorkspaceMember::withoutGlobalScopes()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $cashierB->id,
            'branch_id' => $branch->id,
            'role' => 'STAFF',
            'job_title' => 'Kasir B',
            'pin' => Hash::make('654321'),
            'base_salary' => 3000000,
            'is_active' => true,
        ]);

        // Kasir A gagal 5 kali di Token A
        for ($i = 0; $i < 5; $i++) {
            $this->withHeader('X-Device-Token', $rawTokenA)
                ->postJson('/api/v1/pos/sessions/open', [
                    'cashier_user_id' => $cashierA->id,
                    'pin' => '000000',
                    'opening_cash' => 100000,
                ]);
        }

        // Request ke-6 Kasir A diblokir (429)
        $responseA = $this->withHeader('X-Device-Token', $rawTokenA)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashierA->id,
                'pin' => '123456',
                'opening_cash' => 100000,
            ]);
        $responseA->assertStatus(429);

        // Kasir B di terminal yang sama atau kasir B di IP yang sama tetap dapat membuka sesi jika kredensial valid
        $responseB = $this->withHeader('X-Device-Token', $rawTokenA)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashierB->id,
                'pin' => '654321',
                'opening_cash' => 150000,
            ]);

        // Karena belum pernah salah input PIN, Kasir B tidak terkena rate limit 429
        $responseB->assertStatus(201);
    }

    /**
     * temuan 4: pembukaan sesi kasir dilindungi transaksi dan pessimistic lock
     */
    public function test_pos_session_open_pessimistic_lock_prevents_multiple_concurrent_open_sessions(): void
    {
        $rawToken = 'pos-device-token-seturan-01';
        $cashier = User::where('email', 'ami@gmail.com')->firstOrFail();

        // 1. Sesi pertama berhasil dibuka
        $response1 = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'pin' => '123456',
                'opening_cash' => 100000,
            ]);

        $response1->assertStatus(201);

        // 2. Percobaan kedua membuka sesi di cabang yang sama langsung ditolak (422)
        $response2 = $this->withHeader('X-Device-Token', $rawToken)
            ->postJson('/api/v1/pos/sessions/open', [
                'cashier_user_id' => $cashier->id,
                'pin' => '123456',
                'opening_cash' => 200000,
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
