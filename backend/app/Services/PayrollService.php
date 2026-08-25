<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\DomainConstants;
use App\Mail\PayslipNotificationMailable;
use App\Models\Attendance;
use App\Models\BranchSetting;
use App\Models\CashAdvance;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PayrollService
{
    /**
     * hitung kalkulasi komponen penggajian untuk seorang anggota staf pada periode tertentu
     *
     * @param Collection<int, Attendance>|null $memberAttendances
     * @param Collection<int, CashAdvance>|null $memberCashAdvances
     * @return array<string, mixed>
     */
    public function calculateMemberPayroll(
        WorkspaceMember $member,
        string $periodStart,
        string $periodEnd,
        ?BranchSetting $defaultSetting = null,
        ?BranchSetting $branchSetting = null,
        ?Collection $memberAttendances = null,
        ?Collection $memberCashAdvances = null
    ): array {
        $workspaceId = (string) $member->workspace_id;
        $userId = (string) $member->user_id;
        $branchId = $member->branch_id ? (string) $member->branch_id : null;

        $baseSalary = (float) $member->base_salary;

        // ambil konfigurasi denda telat & tarif lembur cabang
        /** @var BranchSetting|null $setting */
        $setting = $branchSetting;
        if (! $setting && $branchId) {
            $setting = BranchSetting::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('branch_id', $branchId)
                ->first();
        }

        if (! $setting) {
            $setting = $defaultSetting;
        }

        $latePenaltyPerMinute = (float) ($setting?->late_penalty_per_minute ?? DomainConstants::DEFAULT_LATE_PENALTY_PER_MINUTE);
        $overtimePayPerHour = (float) ($setting?->overtime_pay_per_hour ?? DomainConstants::DEFAULT_OVERTIME_PAY_PER_HOUR);
        $overtimePayPerMinute = $overtimePayPerHour / 60.0;

        // akumulasi menit keterlambatan & lembur dari presensi yang disetujui
        $attendances = $memberAttendances ?? Attendance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->where('status', 'APPROVED')
            ->whereBetween('clock_in_time', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->get();

        $totalLateMinutes = (int) $attendances->sum('late_minutes');
        $totalOvertimeMinutes = (int) $attendances->sum('overtime_minutes');

        $latePenalty = round($totalLateMinutes * $latePenaltyPerMinute, 2);
        $overtimePay = round($totalOvertimeMinutes * $overtimePayPerMinute, 2);

        // akumulasi kasbon aktif staf yang belum dilunasi
        $cashAdvances = $memberCashAdvances ?? CashAdvance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->where('status', 'APPROVED')
            ->whereNull('deducted_at_payroll_date')
            ->where('request_date', '<=', $periodEnd)
            ->get();

        $cashAdvanceDeduction = (float) $cashAdvances->sum('amount');

        // kalkulasi gaji bersih: gaji pokok + lembur - denda telat - potongan kasbon
        $netSalary = max(0.00, round($baseSalary + $overtimePay - $latePenalty - $cashAdvanceDeduction, 2));

        return [
            'workspace_member_id' => $member->id,
            'user_id' => $userId,
            'name' => $member->user?->name,
            'email' => $member->user?->email,
            'role' => $member->role,
            'branch_id' => $branchId,
            'branch_name' => $member->branch?->name,
            'bank_name' => $member->user?->bank_name,
            'bank_account_number' => $member->user?->bank_account_number,
            'bank_account_holder' => $member->user?->bank_account_holder,
            'base_salary' => $baseSalary,
            'total_late_minutes' => $totalLateMinutes,
            'late_penalty' => $latePenalty,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'overtime_pay' => $overtimePay,
            'cash_advance_deduction' => $cashAdvanceDeduction,
            'net_salary' => $netSalary,
        ];
    }

    /**
     * ambil pratinjau rekapitulasi penggajian seluruh staf untuk periode tertentu
     *
     * @return array{period_start: string, period_end: string, items: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function calculatePreview(
        string $workspaceId,
        string $periodStart,
        string $periodEnd,
        ?string $branchId = null
    ): array {
        $query = WorkspaceMember::withoutGlobalScopes()
            ->with(['user', 'branch'])
            ->where('workspace_id', $workspaceId)
            ->where('role', '!=', 'OWNER')
            ->where('is_active', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $members = $query->get();

        /** @var BranchSetting|null $defaultSetting */
        $defaultSetting = BranchSetting::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereNull('branch_id')
            ->first();

        $userIds = $members->pluck('user_id')->unique()->toArray();
        $branchIds = $members->pluck('branch_id')->filter()->unique()->toArray();

        // Batch preloading to eliminate N+1 queries
        $branchSettings = BranchSetting::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->keyBy('branch_id');

        $attendancesByUser = Attendance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('user_id', $userIds)
            ->where('status', 'APPROVED')
            ->whereBetween('clock_in_time', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->get()
            ->groupBy('user_id');

        $cashAdvancesByUser = CashAdvance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('user_id', $userIds)
            ->where('status', 'APPROVED')
            ->whereNull('deducted_at_payroll_date')
            ->where('request_date', '<=', $periodEnd)
            ->get()
            ->groupBy('user_id');

        $items = [];
        $totalBase = 0.0;
        $totalOvertime = 0.0;
        $totalLate = 0.0;
        $totalAdvance = 0.0;
        $totalNet = 0.0;

        foreach ($members as $member) {
            $uId = (string) $member->user_id;
            $bId = $member->branch_id ? (string) $member->branch_id : null;

            $bSetting = $bId ? ($branchSettings->get($bId)) : null;
            $userAttendances = $attendancesByUser->get($uId, collect());
            $userCashAdvances = $cashAdvancesByUser->get($uId, collect());

            $calculated = $this->calculateMemberPayroll(
                member: $member,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                defaultSetting: $defaultSetting,
                branchSetting: $bSetting,
                memberAttendances: $userAttendances,
                memberCashAdvances: $userCashAdvances
            );
            $items[] = $calculated;

            $totalBase += $calculated['base_salary'];
            $totalOvertime += $calculated['overtime_pay'];
            $totalLate += $calculated['late_penalty'];
            $totalAdvance += $calculated['cash_advance_deduction'];
            $totalNet += $calculated['net_salary'];
        }

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'items' => $items,
            'totals' => [
                'total_base_salary' => round($totalBase, 2),
                'total_overtime_pay' => round($totalOvertime, 2),
                'total_late_penalty' => round($totalLate, 2),
                'total_cash_advance_deduction' => round($totalAdvance, 2),
                'total_net_salary' => round($totalNet, 2),
            ],
        ];
    }

    /**
     * finalisasi dan eksekusi pencairan gaji (disburse), menyimpan record payroll dan mengubah status kasbon
     *
     * @return array{disbursed_count: int, total_amount: float, period_start: string, period_end: string}
     */
    public function disbursePayroll(
        User $admin,
        string $workspaceId,
        string $periodStart,
        string $periodEnd,
        ?string $branchId = null
    ): array {
        return DB::transaction(function () use ($workspaceId, $periodStart, $periodEnd, $branchId): array {
            $preview = $this->calculatePreview($workspaceId, $periodStart, $periodEnd, $branchId);
            $workspaceName = Workspace::withoutGlobalScopes()->where('id', $workspaceId)->value('name') ?? 'PRÉCIS Workspace';
            $disbursedCount = 0;
            $now = Carbon::now();

            foreach ($preview['items'] as $item) {
                // simpan atau perbarui record payroll
                /** @var Payroll $payroll */
                $payroll = Payroll::withoutGlobalScopes()->updateOrCreate(
                    [
                        'workspace_id' => $workspaceId,
                        'user_id' => $item['user_id'],
                        'workspace_member_id' => $item['workspace_member_id'],
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ],
                    [
                        'base_salary' => $item['base_salary'],
                        'overtime_pay' => $item['overtime_pay'],
                        'late_penalty' => $item['late_penalty'],
                        'cash_advance_deduction' => $item['cash_advance_deduction'],
                        'net_salary' => $item['net_salary'],
                        'status' => 'DISBURSED',
                        'disbursed_at' => $now,
                    ]
                );

                // perbarui status kasbon aktif staf menjadi DEDUCTED
                CashAdvance::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)
                    ->where('user_id', $item['user_id'])
                    ->where('status', 'APPROVED')
                    ->whereNull('deducted_at_payroll_date')
                    ->where('request_date', '<=', $periodEnd)
                    ->update([
                        'status' => 'DEDUCTED',
                        'deducted_at_payroll_date' => $periodEnd,
                    ]);

                // kirim notifikasi email slip gaji jika email staf terisi
                if (! empty($item['email'])) {
                    /** @var User|null $employeeUser */
                    $employeeUser = User::find($item['user_id']);
                    if ($employeeUser) {
                        $slipUrl = config('app.url') . '/payroll/slip';
                        Mail::to($item['email'])->send(new PayslipNotificationMailable(
                            employee: $employeeUser,
                            payroll: $payroll,
                            workspaceName: $workspaceName,
                            slipUrl: $slipUrl,
                            overtimeMinutes: (int) $item['total_overtime_minutes'],
                            lateMinutes: (int) $item['total_late_minutes'],
                        ));
                    }
                }

                $disbursedCount++;
            }

            return [
                'disbursed_count' => $disbursedCount,
                'total_amount' => $preview['totals']['total_net_salary'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ];
        });
    }

    /**
     * hasilkan isi file csv transfer bank (format bca atau mandiri mcm)
     */
    public function generateBankCsv(
        string $workspaceId,
        string $periodStart,
        string $periodEnd,
        string $bankFormat = 'BCA',
        ?string $branchId = null
    ): string {
        $preview = $this->calculatePreview($workspaceId, $periodStart, $periodEnd, $branchId);
        $lines = [];

        $formatUpper = strtoupper($bankFormat);

        if ($formatUpper === 'MANDIRI') {
            // format mandiri mcm / corporate payroll
            $lines[] = 'Beneficiary Account Number,Beneficiary Name,Amount,Currency,Remark';

            foreach ($preview['items'] as $item) {
                $lines[] = sprintf(
                    '"%s","%s",%.2f,IDR,"Gaji Periode %s sd %s"',
                    str_replace('"', '""', (string) ($item['bank_account_number'] ?? '0000000000')),
                    str_replace('"', '""', (string) ($item['bank_account_holder'] ?? $item['name'])),
                    $item['net_salary'],
                    $periodStart,
                    $periodEnd
                );
            }
        } else {
            // format BCA Corporate Bulk Transfer
            $lines[] = 'No,Nama Karyawan,Nomor Rekening,Nominal Transfer,Berita Acara';
            $index = 1;

            foreach ($preview['items'] as $item) {
                $lines[] = sprintf(
                    '%d,"%s","%s",%.2f,"Payroll %s - %s"',
                    $index++,
                    str_replace('"', '""', (string) ($item['bank_account_holder'] ?? $item['name'])),
                    str_replace('"', '""', (string) ($item['bank_account_number'] ?? '0000000000')),
                    $item['net_salary'],
                    $periodStart,
                    $periodEnd
                );
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * ambil rincian slip gaji digital personal untuk staf
     *
     * @return array<string, mixed>|null
     */
    public function getMyPayrollSlip(
        User $user,
        string $workspaceId,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): ?array {
        $query = Payroll::withoutGlobalScopes()
            ->with(['workspaceMember.branch', 'user'])
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id);

        if ($periodStart && $periodEnd) {
            $query->whereDate('period_start', $periodStart)->whereDate('period_end', $periodEnd);
        } else {
            $query->orderByDesc('period_end')->orderByDesc('created_at');
        }

        /** @var Payroll|null $payroll */
        $payroll = $query->first();

        if ($payroll) {
            $member = $payroll->workspaceMember;

            return [
                'id' => $payroll->id,
                'user_name' => $payroll->user?->name,
                'user_email' => $payroll->user?->email,
                'role' => $member?->role,
                'branch_name' => $member?->branch?->name,
                'period_start' => $payroll->period_start instanceof Carbon
                    ? $payroll->period_start->toDateString()
                    : (string) $payroll->period_start,
                'period_end' => $payroll->period_end instanceof Carbon
                    ? $payroll->period_end->toDateString()
                    : (string) $payroll->period_end,
                'base_salary' => (float) $payroll->base_salary,
                'overtime_pay' => (float) $payroll->overtime_pay,
                'late_penalty' => (float) $payroll->late_penalty,
                'cash_advance_deduction' => (float) $payroll->cash_advance_deduction,
                'net_salary' => (float) $payroll->net_salary,
                'status' => $payroll->status,
                'disbursed_at' => $payroll->disbursed_at?->toIso8601String(),
            ];
        }

        // jika belum ada record payroll resmi, hitung perkiraan berjalan bulan ini
        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->with(['user', 'branch'])
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return null;
        }

        $pStart = $periodStart ?? Carbon::today()->startOfMonth()->toDateString();
        $pEnd = $periodEnd ?? Carbon::today()->endOfMonth()->toDateString();

        $calculated = $this->calculateMemberPayroll($member, $pStart, $pEnd);

        return [
            'id' => null,
            'user_name' => $calculated['name'],
            'user_email' => $calculated['email'],
            'role' => $calculated['role'],
            'branch_name' => $calculated['branch_name'],
            'period_start' => $pStart,
            'period_end' => $pEnd,
            'base_salary' => $calculated['base_salary'],
            'overtime_pay' => $calculated['overtime_pay'],
            'late_penalty' => $calculated['late_penalty'],
            'cash_advance_deduction' => $calculated['cash_advance_deduction'],
            'net_salary' => $calculated['net_salary'],
            'status' => 'ESTIMATED',
            'disbursed_at' => null,
        ];
    }
}
