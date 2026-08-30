<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /**
     * ambil kalender roster penugasan shift per cabang dalam rentang tanggal tertentu
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoster(
        string $workspaceId,
        ?string $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::today()->toDateString();
        $end = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::today()->addDays(6)->toDateString();

        $query = ShiftAssignment::withoutGlobalScopes()
            ->with(['template.branch', 'assignedUser', 'actualUser', 'swapApprovedBy'])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('created_at');

        if ($branchId) {
            $query->whereHas('template', function ($q) use ($branchId): void {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->get()->map(function (ShiftAssignment $assignment): array {
            $template = $assignment->template;

            return [
                'id' => $assignment->id,
                'date' => $assignment->date instanceof Carbon ? $assignment->date->toDateString() : (string) $assignment->date,
                'is_swap' => $assignment->is_swap,
                'swap_status' => $assignment->swap_status,
                'template' => $template ? [
                    'id' => $template->id,
                    'name' => $template->name,
                    'branch_id' => $template->branch_id,
                    'branch_name' => $template->branch?->name,
                    'expected_clock_in' => $template->expected_clock_in,
                    'expected_clock_out' => $template->expected_clock_out,
                ] : null,
                'assigned_user' => [
                    'id' => $assignment->assignedUser?->id,
                    'name' => $assignment->assignedUser?->name,
                    'email' => $assignment->assignedUser?->email,
                ],
                'actual_user' => $assignment->actualUser ? [
                    'id' => $assignment->actualUser->id,
                    'name' => $assignment->actualUser->name,
                    'email' => $assignment->actualUser->email,
                ] : null,
                'swap_approved_by' => $assignment->swapApprovedBy ? [
                    'id' => $assignment->swapApprovedBy->id,
                    'name' => $assignment->swapApprovedBy->name,
                    'email' => $assignment->swapApprovedBy->email,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * tetapkan jadwal shift kepada staf pada tanggal tertentu
     */
    public function assignShift(
        User $creator,
        string $workspaceId,
        string $shiftTemplateId,
        string $assignedUserId,
        string $date
    ): ShiftAssignment {
        /** @var ShiftTemplate|null $template */
        $template = ShiftTemplate::withoutGlobalScopes()->where('workspace_id', $workspaceId)->where('id', $shiftTemplateId)->first();
        if (! $template) {
            throw ValidationException::withMessages([
                'shift_template_id' => ['Template shift tidak ditemukan.'],
            ]);
        }

        /** @var WorkspaceMember|null $member */
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where(function ($query) use ($assignedUserId): void {
                $query->where('user_id', $assignedUserId)
                    ->orWhere('id', $assignedUserId);
            })
            ->where('is_active', true)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'assigned_user_id' => ['Karyawan tidak ditemukan atau bukan anggota aktif di workspace ini.'],
            ]);
        }

        $realUserId = (string) $member->user_id;
        $formattedDate = Carbon::parse($date)->toDateString();

        /** @var WorkspaceMember|null $creatorMember */
        $creatorMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $creator->id)
            ->first();

        if ($creatorMember && $creatorMember->role !== 'OWNER' && $creatorMember->branch_id !== null) {
            if ($template->branch_id !== null && $template->branch_id !== $creatorMember->branch_id) {
                abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda hanya berwenang mengelola shift pada cabang penugasan Anda.');
            }
            if ($member->branch_id !== null && $member->branch_id !== $creatorMember->branch_id) {
                abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda hanya berwenang mengelola shift staf pada cabang penugasan Anda.');
            }
        }

        // validasi anti-duplikasi: staf tidak boleh memiliki 2 shift di hari yang sama
        $existing = ShiftAssignment::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('assigned_user_id', $realUserId)
            ->whereDate('date', $formattedDate)
            ->first();

        if ($existing) {
            $existing->update([
                'shift_template_id' => $template->id,
                'is_swap' => false,
                'swap_status' => 'NONE',
                'actual_user_id' => null,
                'swap_approved_by_user_id' => null,
            ]);

            return $existing;
        }

        return ShiftAssignment::create([
            'workspace_id' => $workspaceId,
            'shift_template_id' => $template->id,
            'assigned_user_id' => $realUserId,
            'date' => $formattedDate,
            'is_swap' => false,
            'swap_status' => 'NONE',
            'created_by_user_id' => $creator->id,
        ]);
    }

    /**
     * hapus atau batalkan penugasan shift (menjadikan hari libur / off)
     */
    public function deleteAssignment(string $workspaceId, string $assignmentId, ?User $actor = null): void
    {
        $assignment = ShiftAssignment::withoutGlobalScopes()
            ->with('template')
            ->where('workspace_id', $workspaceId)
            ->where('id', $assignmentId)
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'shift_assignment_id' => ['Penugasan shift tidak ditemukan.'],
            ]);
        }

        if ($actor) {
            /** @var WorkspaceMember|null $actorMember */
            $actorMember = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $actor->id)
                ->first();

            if ($actorMember && $actorMember->role !== 'OWNER' && $actorMember->branch_id !== null) {
                if ($assignment->template && $assignment->template->branch_id !== null && $assignment->template->branch_id !== $actorMember->branch_id) {
                    abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda hanya berwenang mengelola shift pada cabang penugasan Anda.');
                }
            }
        }

        $assignment->delete();
    }

    /**
     * ajukan permohonan tukar shift oleh staf ke staf lain
     */
    public function requestSwap(
        User $requester,
        string $workspaceId,
        string $shiftAssignmentId,
        string $targetUserId
    ): ShiftAssignment {
        /** @var ShiftAssignment|null $assignment */
        $assignment = ShiftAssignment::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $shiftAssignmentId)
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'shift_assignment_id' => ['Penugasan shift tidak ditemukan.'],
            ]);
        }

        if ($assignment->assigned_user_id !== $requester->id) {
            throw ValidationException::withMessages([
                'shift_assignment_id' => ['Anda hanya dapat mengajukan penukaran untuk jadwal shift milik Anda sendiri.'],
            ]);
        }

        $shiftDate = $assignment->date instanceof Carbon ? $assignment->date : Carbon::parse($assignment->date);
        if ($shiftDate->isPast() && ! $shiftDate->isToday()) {
            throw ValidationException::withMessages([
                'shift_assignment_id' => ['Penukaran shift tidak dapat diajukan untuk jadwal yang sudah lampau.'],
            ]);
        }

        /** @var WorkspaceMember|null $targetMember */
        $targetMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where(function ($query) use ($targetUserId): void {
                $query->where('user_id', $targetUserId)
                    ->orWhere('id', $targetUserId);
            })
            ->where('is_active', true)
            ->first();

        if (! $targetMember) {
            throw ValidationException::withMessages([
                'target_user_id' => ['Karyawan pengganti tidak ditemukan atau bukan anggota aktif di workspace ini.'],
            ]);
        }

        $realTargetUserId = (string) $targetMember->user_id;

        if ($realTargetUserId === $requester->id) {
            throw ValidationException::withMessages([
                'target_user_id' => ['Karyawan pengganti tidak boleh sama dengan pemohon.'],
            ]);
        }

        $assignment->update([
            'is_swap' => true,
            'actual_user_id' => $realTargetUserId,
            'swap_status' => 'PENDING',
            'swap_approved_by_user_id' => null,
        ]);

        return $assignment;
    }

    /**
     * ambil daftar permohonan tukar shift yang tertunda (PENDING) untuk antrean persetujuan admin
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendingSwapRequests(string $workspaceId, ?string $branchId = null): array
    {
        $query = ShiftAssignment::withoutGlobalScopes()
            ->with(['template.branch', 'assignedUser', 'actualUser'])
            ->where('workspace_id', $workspaceId)
            ->where('is_swap', true)
            ->where('swap_status', 'PENDING')
            ->orderBy('date');

        if ($branchId) {
            $query->whereHas('template', function ($q) use ($branchId): void {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->get()->map(function (ShiftAssignment $assignment): array {
            $template = $assignment->template;

            return [
                'id' => $assignment->id,
                'date' => $assignment->date instanceof Carbon ? $assignment->date->toDateString() : (string) $assignment->date,
                'template' => $template ? [
                    'id' => $template->id,
                    'name' => $template->name,
                    'branch_id' => $template->branch_id,
                    'branch_name' => $template->branch?->name,
                    'expected_clock_in' => $template->expected_clock_in,
                    'expected_clock_out' => $template->expected_clock_out,
                ] : null,
                'assigned_user' => [
                    'id' => $assignment->assignedUser?->id,
                    'name' => $assignment->assignedUser?->name,
                    'email' => $assignment->assignedUser?->email,
                ],
                'actual_user' => [
                    'id' => $assignment->actualUser?->id,
                    'name' => $assignment->actualUser?->name,
                    'email' => $assignment->actualUser?->email,
                ],
                'created_at' => $assignment->updated_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * setujui permohonan tukar shift oleh ADMIN dan OWNER dalam transaksi database
     */
    public function approveSwap(User $approver, string $workspaceId, string $shiftAssignmentId): ShiftAssignment
    {
        return DB::transaction(function () use ($approver, $workspaceId, $shiftAssignmentId): ShiftAssignment {
            /** @var ShiftAssignment|null $assignment */
            $assignment = ShiftAssignment::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('id', $shiftAssignmentId)
                ->where('is_swap', true)
                ->where('swap_status', 'PENDING')
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw ValidationException::withMessages([
                    'shift_assignment_id' => ['Permohonan tukar shift tidak ditemukan atau sudah diproses.'],
                ]);
            }

            /** @var WorkspaceMember|null $approverMember */
            $approverMember = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $approver->id)
                ->first();

            if ($approverMember && $approverMember->role !== 'OWNER' && $approverMember->branch_id !== null) {
                $assignment->loadMissing('template');
                if ($assignment->template && $assignment->template->branch_id !== null && $assignment->template->branch_id !== $approverMember->branch_id) {
                    abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda hanya berwenang menyetujui tukar shift pada cabang penugasan Anda.');
                }
            }

            $assignment->update([
                'swap_status' => 'APPROVED',
                'swap_approved_by_user_id' => $approver->id,
            ]);

            return $assignment;
        });
    }

    /**
     * tolak permohonan tukar shift oleh ADMIN dan OWNER
     */
    public function rejectSwap(User $approver, string $workspaceId, string $shiftAssignmentId): ShiftAssignment
    {
        return DB::transaction(function () use ($approver, $workspaceId, $shiftAssignmentId): ShiftAssignment {
            /** @var ShiftAssignment|null $assignment */
            $assignment = ShiftAssignment::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('id', $shiftAssignmentId)
                ->where('is_swap', true)
                ->where('swap_status', 'PENDING')
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                throw ValidationException::withMessages([
                    'shift_assignment_id' => ['Permohonan tukar shift tidak ditemukan atau sudah diproses.'],
                ]);
            }

            /** @var WorkspaceMember|null $approverMember */
            $approverMember = WorkspaceMember::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $approver->id)
                ->first();

            if ($approverMember && $approverMember->role !== 'OWNER' && $approverMember->branch_id !== null) {
                $assignment->loadMissing('template');
                if ($assignment->template && $assignment->template->branch_id !== null && $assignment->template->branch_id !== $approverMember->branch_id) {
                    abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda hanya berwenang menolak tukar shift pada cabang penugasan Anda.');
                }
            }

            $assignment->update([
                'swap_status' => 'REJECTED',
                'actual_user_id' => null,
                'swap_approved_by_user_id' => $approver->id,
            ]);

            return $assignment;
        });
    }
}
