<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaaSBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private SubscriptionPlan $plan;
    private Workspace $workspace;
    private Superadmin $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'arief@amorecoffee.id')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();
        $this->plan = SubscriptionPlan::where('is_active', true)->firstOrFail();
        $this->superadmin = Superadmin::firstOrFail();
    }

    public function test_user_can_create_invoice_with_random_3_digit_unique_code(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/invoices', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'MONTHLY',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Faktur tagihan langganan berhasil dibuat.')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'amount_base',
                    'unique_code',
                    'total_amount',
                    'status',
                    'due_date',
                ],
            ]);

        $uniqueCode = $response->json('data.unique_code');
        $this->assertGreaterThanOrEqual(100, $uniqueCode);
        $this->assertLessThanOrEqual(999, $uniqueCode);

        $amountBase = $response->json('data.amount_base');
        $totalAmount = $response->json('data.total_amount');
        $this->assertEquals($amountBase + $uniqueCode, $totalAmount);

        $this->assertDatabaseHas('invoices', [
            'id' => $response->json('data.id'),
            'user_id' => $this->owner->id,
            'status' => 'UNPAID',
        ]);
    }

    public function test_user_can_submit_payment_proof_for_invoice(): void
    {
        $invoice = Invoice::create([
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-20260823-TEST01',
            'amount_base' => 150000.00,
            'unique_code' => 345,
            'total_amount' => 150345.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/billing/invoices/{$invoice->id}/proof", [
            'bank_account_name' => 'Arief Hadinata',
            'transfer_amount' => 150345,
            'proof_image_url' => 'https://mock.storage/proof.webp',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi.')
            ->assertJsonPath('data.bank_account_name', 'Arief Hadinata')
            ->assertJsonPath('data.transfer_amount', 150345);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PENDING_VERIFICATION',
        ]);

        $this->assertDatabaseHas('payment_confirmations', [
            'invoice_id' => $invoice->id,
            'bank_account_name' => 'Arief Hadinata',
        ]);
    }

    public function test_superadmin_can_verify_invoice_and_extend_user_subscription(): void
    {
        $invoice = Invoice::create([
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-20260823-TEST02',
            'amount_base' => 150000.00,
            'unique_code' => 567,
            'total_amount' => 150567.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'PENDING_VERIFICATION',
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

        $this->owner->refresh();
        $this->assertEquals('ACTIVE', $this->owner->subscription_status);
        $this->assertNotNull($this->owner->subscription_expires_at);
        $this->assertTrue(Carbon::parse($this->owner->subscription_expires_at)->isFuture());
    }

    public function test_user_can_view_own_invoices_history(): void
    {
        Invoice::create([
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-20260823-HIST01',
            'amount_base' => 150000.00,
            'unique_code' => 101,
            'total_amount' => 150101.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'PAID',
        ]);

        Invoice::create([
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-20260823-HIST02',
            'amount_base' => 150000.00,
            'unique_code' => 102,
            'total_amount' => 150102.00,
            'due_date' => Carbon::now()->addDays(3),
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk()
            ->assertJsonPath('message', 'Riwayat faktur tagihan berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'amount_base',
                        'unique_code',
                        'total_amount',
                        'status',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_workspace_access_blocked_when_owner_is_suspended(): void
    {
        $this->owner->update(['subscription_status' => 'SUSPENDED']);

        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/workspace/context');

        $response->assertStatus(402)
            ->assertJsonPath('subscription_status', 'SUSPENDED');
    }

    public function test_workspace_access_allowed_with_warning_during_grace_period(): void
    {
        // Kadaluwarsa 2 hari yang lalu (masih dalam grace period 5 hari)
        $this->owner->update([
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => Carbon::now()->subDays(2),
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/workspace/context');

        $response->assertOk()
            ->assertHeader('X-Subscription-Warning', 'GRACE_PERIOD');
    }
}
