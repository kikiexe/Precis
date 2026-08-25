<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\PaymentConfirmation;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminApiTest extends TestCase
{
    use RefreshDatabase;

    private Superadmin $superadmin;
    private User $tenantOwner;
    private Workspace $workspace;
    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superadmin = Superadmin::where('email', 'root@precis.com')->firstOrFail();
        $this->tenantOwner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->plan = SubscriptionPlan::where('is_active', true)->firstOrFail();
    }

    public function test_superadmin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/superadmin/auth/login', [
            'email' => 'root@precis.com',
            'password' => 'PrecisAdmin2026!',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Autentikasi Superadmin berhasil.')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'superadmin' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_superadmin_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/superadmin/auth/login', [
            'email' => 'root@precis.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_superadmin_can_get_profile_and_logout(): void
    {
        Sanctum::actingAs($this->superadmin);

        $meResponse = $this->getJson('/api/v1/superadmin/auth/me');
        $meResponse->assertOk()
            ->assertJsonPath('data.email', 'root@precis.com');

        $logoutResponse = $this->postJson('/api/v1/superadmin/auth/logout');
        $logoutResponse->assertOk()
            ->assertJsonPath('message', 'Sesi Superadmin telah diakhiri.');
    }

    public function test_superadmin_can_list_invoices_and_filter_by_status(): void
    {
        $invoice = Invoice::create([
            'user_id' => $this->tenantOwner->id,
            'invoice_number' => 'INV-20260823-SUP01',
            'amount_base' => 150000.00,
            'unique_code' => 321,
            'total_amount' => 150321.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'PENDING_VERIFICATION',
        ]);

        PaymentConfirmation::create([
            'invoice_id' => $invoice->id,
            'user_id' => $this->tenantOwner->id,
            'bank_account_name' => 'Arief Hadinata',
            'transfer_amount' => 150321,
            'proof_image_url' => 'https://mock.storage/proof.webp',
        ]);

        Sanctum::actingAs($this->superadmin);

        $response = $this->getJson('/api/v1/superadmin/invoices?status=PENDING_VERIFICATION');

        $response->assertOk()
            ->assertJsonPath('message', 'Daftar faktur berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'amount_base',
                        'unique_code',
                        'total_amount',
                        'status',
                        'user',
                        'confirmation',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_superadmin_can_verify_invoice_and_activate_tenant(): void
    {
        $invoice = Invoice::create([
            'user_id' => $this->tenantOwner->id,
            'invoice_number' => 'INV-20260823-SUP02',
            'amount_base' => 150000.00,
            'unique_code' => 456,
            'total_amount' => 150456.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'PENDING_VERIFICATION',
        ]);

        PaymentConfirmation::create([
            'invoice_id' => $invoice->id,
            'user_id' => $this->tenantOwner->id,
            'bank_account_name' => 'Arief Hadinata',
            'transfer_amount' => 150456,
            'proof_image_url' => 'https://mock.storage/proof.webp',
        ]);

        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson("/api/v1/superadmin/invoices/{$invoice->id}/verify");

        $response->assertOk()
            ->assertJsonPath('message', 'Pembayaran invoice berhasil diverifikasi dan langganan telah diperpanjang.')
            ->assertJsonPath('data.status', 'PAID')
            ->assertJsonPath('data.subscription_status', 'ACTIVE');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PAID',
        ]);
    }

    public function test_superadmin_can_view_saas_metrics(): void
    {
        Sanctum::actingAs($this->superadmin);

        $response = $this->getJson('/api/v1/superadmin/metrics');

        $response->assertOk()
            ->assertJsonPath('message', 'Metrik global SaaS berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    'mrr',
                    'arr',
                    'total_revenue',
                    'tenants' => [
                        'total',
                        'active',
                        'grace_period',
                        'suspended',
                        'trial',
                    ],
                    'total_branches',
                    'invoices' => [
                        'pending',
                        'unpaid',
                        'paid',
                    ],
                    'timestamp',
                ],
            ]);
    }

    public function test_superadmin_can_list_tenants_and_update_status(): void
    {
        Sanctum::actingAs($this->superadmin);

        $listResponse = $this->getJson('/api/v1/superadmin/tenants');
        $listResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'subscription_status',
                        'workspaces',
                    ],
                ],
            ]);

        // suspend tenant
        $suspendResponse = $this->postJson("/api/v1/superadmin/tenants/{$this->tenantOwner->id}/status", [
            'status' => 'SUSPENDED',
        ]);

        $suspendResponse->assertOk()
            ->assertJsonPath('data.subscription_status', 'SUSPENDED');

        $this->tenantOwner->refresh();
        $this->assertEquals('SUSPENDED', $this->tenantOwner->subscription_status);

        // aktifkan kembali tenant
        $reactivateResponse = $this->postJson("/api/v1/superadmin/tenants/{$this->tenantOwner->id}/status", [
            'status' => 'ACTIVE',
        ]);

        $reactivateResponse->assertOk()
            ->assertJsonPath('data.subscription_status', 'ACTIVE');

        $this->tenantOwner->refresh();
        $this->assertEquals('ACTIVE', $this->tenantOwner->subscription_status);
    }

    public function test_superadmin_can_extend_tenant_subscription_manually(): void
    {
        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson("/api/v1/superadmin/tenants/{$this->tenantOwner->id}/extend", [
            'days' => 60,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.subscription_status', 'ACTIVE');

        $this->tenantOwner->refresh();
        $this->assertEquals('ACTIVE', $this->tenantOwner->subscription_status);
        $this->assertNotNull($this->tenantOwner->subscription_expires_at);
        $this->assertTrue(Carbon::parse($this->tenantOwner->subscription_expires_at)->isFuture());
    }

    public function test_superadmin_can_list_master_subscription_plans(): void
    {
        Sanctum::actingAs($this->superadmin);

        $response = $this->getJson('/api/v1/superadmin/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'max_workspaces',
                        'monthly_price',
                        'annual_price',
                        'is_active',
                    ],
                ],
            ]);
    }
}
