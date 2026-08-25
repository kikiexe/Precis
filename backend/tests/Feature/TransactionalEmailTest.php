<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\InvoiceReceiptMailable;
use App\Mail\PayslipNotificationMailable;
use App\Mail\ResetPasswordMailable;
use App\Mail\VerifyEmailMailable;
use App\Mail\WorkspaceInvitationMailable;
use App\Models\Invoice;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\BillingService;
use App\Services\PayrollService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
    }

    public function test_register_dispatches_verify_email_mailable(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Doni Owner',
            'email' => 'doni.barista@gmail.com',
            'password' => 'DoniPass123!',
            'workspace_name' => 'Doni Artisan Coffee',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'doni.barista@gmail.com');

        Mail::assertSent(VerifyEmailMailable::class, function (VerifyEmailMailable $mail): bool {
            return $mail->hasTo('doni.barista@gmail.com')
                && str_contains($mail->envelope()->subject, 'Verifikasi');
        });
    }

    public function test_forgot_password_dispatches_reset_password_mailable(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'kiki@gmail.com',
        ]);

        $response->assertOk();

        Mail::assertSent(ResetPasswordMailable::class, function (ResetPasswordMailable $mail): bool {
            return $mail->hasTo('kiki@gmail.com')
                && str_contains($mail->envelope()->subject, 'Pemulihan Kata Sandi');
        });
    }

    public function test_disburse_payroll_dispatches_payslip_mailable(): void
    {
        Mail::fake();

        /** @var PayrollService $payrollService */
        $payrollService = app(PayrollService::class);

        $payrollService->disbursePayroll(
            admin: $this->owner,
            workspaceId: $this->workspace->id,
            periodStart: '2026-08-01',
            periodEnd: '2026-08-31',
        );

        Mail::assertSent(PayslipNotificationMailable::class);
    }

    public function test_invoice_verification_dispatches_invoice_receipt_mailable(): void
    {
        Mail::fake();

        /** @var Superadmin $superadmin */
        $superadmin = Superadmin::firstOrFail();

        $invoice = Invoice::create([
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-20260824-TEST01',
            'amount_base' => 150000.00,
            'unique_code' => 321,
            'total_amount' => 150321.00,
            'status' => 'PENDING_VERIFICATION',
            'due_date' => now()->addDays(3),
        ]);

        /** @var BillingService $billingService */
        $billingService = app(BillingService::class);
        $billingService->verifyInvoicePayment($superadmin, $invoice->id);

        Mail::assertSent(InvoiceReceiptMailable::class, function (InvoiceReceiptMailable $mail): bool {
            return $mail->hasTo('kiki@gmail.com');
        });
    }

    public function test_all_five_mailables_render_valid_html(): void
    {
        // 1. VerifyEmailMailable
        $verifyMailable = new VerifyEmailMailable($this->owner, 'test-token', 'http://localhost:5174/verify');
        $html1 = $verifyMailable->render();
        $this->assertStringContainsString('Verifikasi Akun PRÉCIS Anda', $html1);
        $this->assertStringContainsString('PRÉCIS', $html1);

        // 2. ResetPasswordMailable
        $resetMailable = new ResetPasswordMailable($this->owner, 'test-token', 'http://localhost:5174/reset');
        $html2 = $resetMailable->render();
        $this->assertStringContainsString('Atur Ulang Kata Sandi Akun', $html2);

        // 3. WorkspaceInvitationMailable
        $invitation = new WorkspaceInvitation([
            'workspace_id' => $this->workspace->id,
            'email' => 'calon@test.com',
            'job_title' => 'Barista',
            'role' => 'STAFF',
            'expires_at' => now()->addDays(7),
        ]);
        $invitation->setRelation('workspace', $this->workspace);
        $invitation->setRelation('invitedBy', $this->owner);

        $inviteMailable = new WorkspaceInvitationMailable($invitation, 'http://localhost:5174/invite?token=abc');
        $html3 = $inviteMailable->render();
        $this->assertStringContainsString('Norde Coffee', $html3);
        $this->assertStringContainsString('Barista', $html3);

        // 4. PayslipNotificationMailable
        $payroll = new \App\Models\Payroll([
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'base_salary' => 3000000,
            'overtime_pay' => 100000,
            'late_penalty' => 20000,
            'cash_advance_deduction' => 0,
            'net_salary' => 3080000,
        ]);
        $payslipMailable = new PayslipNotificationMailable($this->owner, $payroll, 'Norde Coffee', 'http://localhost:5174/slip');
        $html4 = $payslipMailable->render();
        $this->assertStringContainsString('PEMBERITAHUAN GAJI', $html4);
        $this->assertStringContainsString('3.080.000', $html4);

        // 5. InvoiceReceiptMailable
        $invoice = new Invoice([
            'invoice_number' => 'INV-20260824-001',
            'total_amount' => 250000,
        ]);
        $invoiceMailable = new InvoiceReceiptMailable($this->owner, $invoice, 'http://localhost:5174/billing');
        $html5 = $invoiceMailable->render();
        $this->assertStringContainsString('Bukti Pembayaran Langganan PRÉCIS', $html5);
    }
}
