<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipNotificationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly Payroll $payroll,
        public readonly string $workspaceName,
        public readonly string $slipUrl,
        public readonly int $overtimeMinutes = 0,
        public readonly int $lateMinutes = 0,
    ) {
    }

    public function envelope(): Envelope
    {
        $pStart = is_string($this->payroll->period_start)
            ? $this->payroll->period_start
            : $this->payroll->period_start?->toDateString();
        $pEnd = is_string($this->payroll->period_end)
            ? $this->payroll->period_end
            : $this->payroll->period_end?->toDateString();

        return new Envelope(
            subject: "Pemberitahuan Slip Gaji Digital Periode {$pStart} s/d {$pEnd}",
        );
    }

    public function content(): Content
    {
        $pStart = is_string($this->payroll->period_start)
            ? $this->payroll->period_start
            : $this->payroll->period_start?->toDateString();
        $pEnd = is_string($this->payroll->period_end)
            ? $this->payroll->period_end
            : $this->payroll->period_end?->toDateString();

        return new Content(
            view: 'emails.payroll.payslip-ready',
            with: [
                'employeeName' => $this->employee->name,
                'workspaceName' => $this->workspaceName,
                'periodStart' => $pStart,
                'periodEnd' => $pEnd,
                'baseSalary' => (float) $this->payroll->base_salary,
                'overtimePay' => (float) $this->payroll->overtime_pay,
                'overtimeMinutes' => $this->overtimeMinutes,
                'latePenalty' => (float) $this->payroll->late_penalty,
                'lateMinutes' => $this->lateMinutes,
                'cashAdvanceDeduction' => (float) $this->payroll->cash_advance_deduction,
                'netSalary' => (float) $this->payroll->net_salary,
                'slipUrl' => $this->slipUrl,
            ],
        );
    }
}
