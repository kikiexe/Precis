<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\CashAdvance;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\PayrollService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollMathematicalPrecisionTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $service;
    private Workspace $workspace;
    private Branch $branch;
    private User $staff;
    private WorkspaceMember $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->service = new PayrollService();
        $this->workspace = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()->where('workspace_id', $this->workspace->id)->firstOrFail();
        $this->staff = User::where('email', 'ami@gmail.com')->firstOrFail();

        $this->member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $this->workspace->id)
            ->where('user_id', $this->staff->id)
            ->firstOrFail();

        // buat setting cabang: tarif denda 1.000/menit, tarif lembur 20.000/jam (333.3333/menit)
        BranchSetting::updateOrCreate(
            ['workspace_id' => $this->workspace->id, 'branch_id' => $this->branch->id],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
            ]
        );
    }

    public function test_net_salary_equals_base_salary_when_no_penalties_or_overtime(): void
    {
        $this->member->update(['base_salary' => 3000000.00]);

        $result = $this->service->calculateMemberPayroll(
            member: $this->member,
            periodStart: '2026-08-01',
            periodEnd: '2026-08-31',
        );

        $this->assertEquals(3000000.00, $result['base_salary']);
        $this->assertEquals(0.00, $result['overtime_pay']);
        $this->assertEquals(0.00, $result['late_penalty']);
        $this->assertEquals(0.00, $result['cash_advance_deduction']);
        $this->assertEquals(3000000.00, $result['net_salary']);
    }

    public function test_net_salary_cannot_drop_below_zero_on_extreme_penalties(): void
    {
        $this->member->update(['base_salary' => 1000000.00]);

        // buat absensi dengan denda telat 1.500 menit * 1.000 = 1.500.000 (melebihi gaji pokok)
        Attendance::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->staff->id,
            'clock_in_time' => Carbon::parse('2026-08-10 09:00:00'),
            'photo_in_url' => 'https://mock.storage/in.webp',
            'lat_in' => -7.7700,
            'lng_in' => 110.3700,
            'late_minutes' => 1500,
            'status' => 'APPROVED',
        ]);

        $result = $this->service->calculateMemberPayroll(
            member: $this->member,
            periodStart: '2026-08-01',
            periodEnd: '2026-08-31',
        );

        $this->assertEquals(1000000.00, $result['base_salary']);
        $this->assertEquals(1500000.00, $result['late_penalty']);
        // net salary harus tertahan di 0.00 dan tidak boleh negatif
        $this->assertEquals(0.00, $result['net_salary']);
    }

    public function test_fractional_overtime_minutes_precision_calculation(): void
    {
        $this->member->update(['base_salary' => 2000000.00]);

        // 45 menit lembur pada tarif 20.000/jam (20.000 * 45 / 60 = 15.000)
        Attendance::create([
            'workspace_id' => $this->workspace->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->staff->id,
            'clock_in_time' => Carbon::parse('2026-08-10 08:00:00'),
            'photo_in_url' => 'https://mock.storage/in.webp',
            'lat_in' => -7.7700,
            'lng_in' => 110.3700,
            'clock_out_time' => Carbon::parse('2026-08-10 16:45:00'),
            'overtime_minutes' => 45,
            'status' => 'APPROVED',
        ]);

        $result = $this->service->calculateMemberPayroll(
            member: $this->member,
            periodStart: '2026-08-01',
            periodEnd: '2026-08-31',
        );

        $this->assertEquals(15000.00, $result['overtime_pay']);
        $this->assertEquals(2015000.00, $result['net_salary']);
    }

    public function test_multiple_cash_advances_deduction_aggregation(): void
    {
        $this->member->update(['base_salary' => 3000000.00]);

        // buat 2 kasbon terpisah: 250.000 dan 450.000
        CashAdvance::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->staff->id,
            'amount' => 250000.00,
            'request_date' => '2026-08-05',
            'status' => 'APPROVED',
        ]);

        CashAdvance::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->staff->id,
            'amount' => 450000.00,
            'request_date' => '2026-08-15',
            'status' => 'APPROVED',
        ]);

        $result = $this->service->calculateMemberPayroll(
            member: $this->member,
            periodStart: '2026-08-01',
            periodEnd: '2026-08-31',
        );

        $this->assertEquals(700000.00, $result['cash_advance_deduction']);
        // net: 3.000.000 - 700.000 = 2.300.000
        $this->assertEquals(2300000.00, $result['net_salary']);
    }
}
