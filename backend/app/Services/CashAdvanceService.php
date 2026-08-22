<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashAdvance;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CashAdvanceService
{
    /**
     * ajukan permohonan kasbon baru oleh staf
     */
    public function requestCashAdvance(User $user, string $workspaceId, float $amount): CashAdvance
    {
        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'user_id' => ['Anda bukan anggota aktif di workspace ini.'],
            ]);
        }

        // cek apakah staf masih memiliki permohonan kasbon yang berstatus PENDING
        $existingPending = CashAdvance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'amount' => ['Anda masih memiliki permohonan kasbon yang sedang menunggu persetujuan (PENDING).'],
            ]);
        }

        return CashAdvance::create([
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'amount' => $amount,
            'request_date' => Carbon::today()->toDateString(),
            'status' => 'PENDING',
        ]);
    }

    /**
     * ambil riwayat permohonan kasbon pribadi staf
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMyCashAdvances(User $user, string $workspaceId): array
    {
        return CashAdvance::withoutGlobalScopes()
            ->with(['user', 'approvedBy'])
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CashAdvance $item): array => $this->formatCashAdvance($item))
            ->toArray();
    }

    /**
     * ambil daftar permohonan kasbon seluruh staf untuk antrean persetujuan admin
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAdminCashAdvances(
        string $workspaceId,
        ?string $status = null,
        ?string $branchId = null
    ): array {
        $query = CashAdvance::withoutGlobalScopes()
            ->with(['user', 'approvedBy'])
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('request_date')
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($branchId) {
            $query->whereHas('user.workspaceMembers', function ($q) use ($workspaceId, $branchId): void {
                $q->where('workspace_id', $workspaceId)->where('branch_id', $branchId);
            });
        }

        return $query->get()
            ->map(fn (CashAdvance $item): array => $this->formatCashAdvance($item))
            ->toArray();
    }

    /**
     * setujui permohonan pencairan kasbon oleh Admin/Owner
     */
    public function approveCashAdvance(User $approver, string $workspaceId, string $cashAdvanceId): CashAdvance
    {
        /** @var CashAdvance|null $advance */
        $advance = CashAdvance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $cashAdvanceId)
            ->where('status', 'PENDING')
            ->first();

        if (! $advance) {
            throw ValidationException::withMessages([
                'cash_advance_id' => ['Permohonan kasbon tidak ditemukan atau sudah diproses.'],
            ]);
        }

        $advance->update([
            'status' => 'APPROVED',
            'approved_by_user_id' => $approver->id,
        ]);

        return $advance;
    }

    /**
     * tolak permohonan kasbon oleh Admin/Owner
     */
    public function rejectCashAdvance(User $approver, string $workspaceId, string $cashAdvanceId): CashAdvance
    {
        /** @var CashAdvance|null $advance */
        $advance = CashAdvance::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $cashAdvanceId)
            ->where('status', 'PENDING')
            ->first();

        if (! $advance) {
            throw ValidationException::withMessages([
                'cash_advance_id' => ['Permohonan kasbon tidak ditemukan atau sudah diproses.'],
            ]);
        }

        $advance->update([
            'status' => 'REJECTED',
            'approved_by_user_id' => $approver->id,
        ]);

        return $advance;
    }

    /**
     * format data kasbon ke dalam array respon yang konsisten
     *
     * @return array<string, mixed>
     */
    private function formatCashAdvance(CashAdvance $advance): array
    {
        return [
            'id' => $advance->id,
            'user' => [
                'id' => $advance->user?->id,
                'name' => $advance->user?->name,
                'email' => $advance->user?->email,
            ],
            'amount' => (float) $advance->amount,
            'request_date' => $advance->request_date instanceof Carbon
                ? $advance->request_date->toDateString()
                : (string) $advance->request_date,
            'status' => $advance->status,
            'approved_by' => $advance->approvedBy ? [
                'id' => $advance->approvedBy->id,
                'name' => $advance->approvedBy->name,
                'email' => $advance->approvedBy->email,
            ] : null,
            'deducted_at_payroll_date' => $advance->deducted_at_payroll_date instanceof Carbon
                ? $advance->deducted_at_payroll_date->toDateString()
                : ($advance->deducted_at_payroll_date ? (string) $advance->deducted_at_payroll_date : null),
            'created_at' => $advance->created_at?->toIso8601String(),
        ];
    }
}
