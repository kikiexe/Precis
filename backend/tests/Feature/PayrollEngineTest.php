<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CashAdvance;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staff;
    private Workspace $workspace;
    private Branch $branch;
    private WorkspaceMember $staffMember;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'kiki@gmail.com')->firstOrFail();
        $this->staff = User::where('email', 'ami@gmail.com')->firstOrFail();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->where('name', 'like', '%Seturan%')->firstOrFail();

        $this->staffMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $this->workspace->id)
            ->where('user_id', $this->staff->id)
            ->firstOrFail();

        // tetapkan gaji pokok staf untuk pengujian
        $this->staffMember->update(['base_salary' => 2500000.00]);
    }

    public function test_admin_can_preview_monthly_payroll_calculation(): void
    {
        $periodStart = '2026-08-01';
        $periodEnd = '2026-08-31';

        // 1. buat data presensi staf dengan 30 menit telat dan 60 menit lembur
        $shiftTemplate = ShiftTemplate::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'name' => 'Shift Pagi Test',
            'expected_clock_in' => '08:00:00',
            'expected_clock_out' => '16:00:00',
        ]);

        $assignment = ShiftAssignment::create([
            'workspace_id' => $this->workspace->id,
            'assigned_user_id' => $this->staff->id,
            'shift_template_id' => $shiftTemplate->id,
            'date' => '2026-08-10',
        ]);

        Attendance::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->staff->id,
            'shift_assignment_id' => $assignment->id,
            'clock_in_time' => Carbon::parse('2026-08-10 08:30:00'),
            'photo_in_url' => 'https://mock.storage/in.webp',
            'lat_in' => -7.7700,
            'lng_in' => 110.3700,
            'late_minutes' => 30, // denda 30 * 1000 = 30.000
            'clock_out_time' => Carbon::parse('2026-08-10 17:00:00'),
            'overtime_minutes' => 60, // lembur 1 jam * 20.000 = 20.000
            'status' => 'APPROVED',
        ]);

        // 2. buat kasbon aktif sebesar 200.000
        CashAdvance::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->staff->id,
            'amount' => 200000.00,
            'request_date' => '2026-08-05',
            'status' => 'APPROVED',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson("/api/v1/admin/payroll/preview?period_start={$periodStart}&period_end={$periodEnd}");

        $response->assertOk()
            ->assertJsonPath('message', 'Pratinjau rekapitulasi penggajian berhasil dimuat.')
            ->assertJsonStructure([
                'data' => [
                    'period_start',
                    'period_end',
                    'items' => [
                        '*' => [
                            'workspace_member_id',
                            'user_id',
                            'name',
                            'base_salary',
                            'total_late_minutes',
                            'late_penalty',
                            'total_overtime_minutes',
                            'overtime_pay',
                            'cash_advance_deduction',
                            'net_salary',
                        ],
                    ],
                    'totals',
                ],
            ]);

        // cari data staf Siti pada items
        $sitiPayroll = collect($response->json('data.items'))
            ->firstWhere('user_id', $this->staff->id);

        $this->assertNotNull($sitiPayroll);
        $this->assertEquals(2500000.00, $sitiPayroll['base_salary']);
        $this->assertEquals(30000.00, $sitiPayroll['late_penalty']);
        $this->assertEquals(20000.00, $sitiPayroll['overtime_pay']);
        $this->assertEquals(200000.00, $sitiPayroll['cash_advance_deduction']);
        // net: 2.500.000 + 20.000 - 30.000 - 200.000 = 2.290.000
        $this->assertEquals(2290000.00, $sitiPayroll['net_salary']);
    }

    public function test_admin_can_disburse_payroll_and_deduct_cash_advances(): void
    {
        $periodStart = '2026-08-01';
        $periodEnd = '2026-08-31';

        $cashAdvance = CashAdvance::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->staff->id,
            'amount' => 150000.00,
            'request_date' => '2026-08-05',
            'status' => 'APPROVED',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/admin/payroll/disburse', [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Pencairan penggajian berhasil dieksekusi.')
            ->assertJsonPath('data.disbursed_count', 5); // 5 staf (manager & 4 kasir) di seeder

        $this->assertDatabaseHas('payrolls', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->staff->id,
            'status' => 'DISBURSED',
        ]);

        // pastikan kasbon berubah status menjadi DEDUCTED
        $this->assertDatabaseHas('cash_advances', [
            'id' => $cashAdvance->id,
            'status' => 'DEDUCTED',
        ]);
    }

    public function test_admin_can_export_payroll_to_bca_and_mandiri_csv(): void
    {
        $periodStart = '2026-08-01';
        $periodEnd = '2026-08-31';

        Sanctum::actingAs($this->owner);

        // uji ekspor format BCA
        $bcaResponse = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->get("/api/v1/admin/payroll/export-csv?period_start={$periodStart}&period_end={$periodEnd}&format=BCA");

        $bcaResponse->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('No,Nama Karyawan,Nomor Rekening,Nominal Transfer,Berita Acara', $bcaResponse->getContent());

        // uji ekspor format MANDIRI
        $mandiriResponse = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->get("/api/v1/admin/payroll/export-csv?period_start={$periodStart}&period_end={$periodEnd}&format=MANDIRI");

        $mandiriResponse->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Beneficiary Account Number,Beneficiary Name,Amount,Currency,Remark', $mandiriResponse->getContent());
    }

    public function test_staff_can_view_own_payroll_slip(): void
    {
        $periodStart = '2026-08-01';
        $periodEnd = '2026-08-31';

        // disburse terlebih dahulu
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/admin/payroll/disburse', [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ])->assertOk();

        // staf melihat slip gajinya sendiri
        Sanctum::actingAs($this->staff);

        $response = $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson("/api/v1/payroll/my-slip?period_start={$periodStart}&period_end={$periodEnd}");

        $response->assertOk()
            ->assertJsonPath('message', 'Rincian slip gaji berhasil dimuat.')
            ->assertJsonPath('data.base_salary', 2500000)
            ->assertJsonPath('data.status', 'DISBURSED');
    }

    public function test_staff_is_forbidden_from_admin_payroll_endpoints(): void
    {
        Sanctum::actingAs($this->staff);

        $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/admin/payroll/preview')
            ->assertStatus(403);

        $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->postJson('/api/v1/admin/payroll/disburse', [
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
            ])
            ->assertStatus(403);

        $this->withHeader('X-Workspace-Id', $this->workspace->id)
            ->getJson('/api/v1/admin/payroll/export-csv?period_start=2026-08-01&period_end=2026-08-31')
            ->assertStatus(403);
    }
}
