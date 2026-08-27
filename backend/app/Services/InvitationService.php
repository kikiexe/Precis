<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\WorkspaceInvitationMailable;
use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    private const INVITATION_EXPIRATION_DAYS = 30;

    /**
     * buat undangan tim baru ke alamat email calon staf dan kirimkan email undangan
     *
     * @param array{email: string, job_title: string, role: string, base_salary: float|int, branch_id?: string|null} $data
     */
    public function createInvitation(User $inviter, string $workspaceId, array $data): WorkspaceInvitation
    {
        $email = strtolower(trim($data['email']));
        $workspace = Workspace::findOrFail($workspaceId);

        // periksa apakah email sudah terdaftar sebagai anggota aktif di workspace ini
        $existingMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereHas('user', function ($query) use ($email): void {
                $query->where('email', $email);
            })
            ->first();

        if ($existingMember) {
            throw ValidationException::withMessages([
                'email' => ['Pengguna dengan email ini sudah terdaftar sebagai anggota tim aktif di workspace ini.'],
            ]);
        }

        // validasi branch_id jika ada
        $branchId = null;
        if (! empty($data['branch_id'])) {
            $branchExists = Branch::where('id', $data['branch_id'])
                ->where('workspace_id', $workspaceId)
                ->exists();
            if ($branchExists) {
                $branchId = $data['branch_id'];
            }
        }

        $roleId = $data['role_id'] ?? null;
        $roleName = $data['role'] ?? 'STAFF';

        if ($roleId) {
            $roleModel = \App\Models\WorkspaceRole::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->find($roleId);
            if ($roleModel) {
                $roleName = $roleModel->name;
                $roleId = $roleModel->id;
            } else {
                $roleId = null;
            }
        }

        return DB::transaction(function () use ($inviter, $workspace, $workspaceId, $email, $data, $branchId, $roleId, $roleName): WorkspaceInvitation {
            // batalkan undangan pending sebelumnya untuk email yang sama di workspace ini
            WorkspaceInvitation::where('workspace_id', $workspaceId)
                ->where('email', $email)
                ->where('status', 'PENDING')
                ->update(['status' => 'CANCELLED']);

            $token = Str::random(64);
            $expiresAt = Carbon::now()->addDays(self::INVITATION_EXPIRATION_DAYS);

            /** @var WorkspaceInvitation $invitation */
            $invitation = WorkspaceInvitation::create([
                'workspace_id' => $workspaceId,
                'invited_by_user_id' => $inviter->id,
                'email' => $email,
                'job_title' => trim($data['job_title']),
                'role' => $roleName,
                'role_id' => $roleId,
                'base_salary' => (float) $data['base_salary'],
                'branch_id' => $branchId,
                'token' => $token,
                'status' => 'PENDING',
                'expires_at' => $expiresAt,
            ]);

            $invitation->load(['workspace', 'invitedBy', 'branch', 'customRole']);

            $inviteUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/invite?token=' . $token;

            Mail::to($email)->send(new WorkspaceInvitationMailable($invitation, $inviteUrl));

            return $invitation;
        });
    }

    /**
     * ambil daftar undangan yang sedang pending di workspace
     *
     * @return Collection<int, WorkspaceInvitation>
     */
    public function getPendingInvitations(string $workspaceId): Collection
    {
        return WorkspaceInvitation::with(['branch', 'customRole'])
            ->where('workspace_id', $workspaceId)
            ->where('status', 'PENDING')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * batalkan undangan yang masih pending
     */
    public function cancelInvitation(string $workspaceId, string $invitationId): WorkspaceInvitation
    {
        /** @var WorkspaceInvitation|null $invitation */
        $invitation = WorkspaceInvitation::where('workspace_id', $workspaceId)
            ->where('id', $invitationId)
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'invitation_id' => ['Undangan tidak ditemukan.'],
            ]);
        }

        if ($invitation->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'invitation_id' => ['Hanya undangan berstatus PENDING yang dapat dibatalkan.'],
            ]);
        }

        $invitation->update(['status' => 'CANCELLED']);

        return $invitation;
    }

    /**
     * kirim ulang email undangan
     */
    public function resendInvitation(string $workspaceId, string $invitationId): WorkspaceInvitation
    {
        /** @var WorkspaceInvitation|null $invitation */
        $invitation = WorkspaceInvitation::with(['workspace', 'invitedBy', 'branch', 'customRole'])
            ->where('workspace_id', $workspaceId)
            ->where('id', $invitationId)
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'invitation_id' => ['Undangan tidak ditemukan.'],
            ]);
        }

        if ($invitation->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'invitation_id' => ['Hanya undangan berstatus PENDING yang dapat dikirim ulang.'],
            ]);
        }

        $newToken = Str::random(64);
        $newExpiresAt = Carbon::now()->addDays(self::INVITATION_EXPIRATION_DAYS);

        $invitation->update([
            'token' => $newToken,
            'expires_at' => $newExpiresAt,
        ]);

        $inviteUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/invite?token=' . $newToken;

        Mail::to($invitation->email)->send(new WorkspaceInvitationMailable($invitation, $inviteUrl));

        return $invitation;
    }

    /**
     * ambil informasi undangan berdasarkan token publik
     */
    public function getInvitationByToken(string $token): WorkspaceInvitation
    {
        /** @var WorkspaceInvitation|null $invitation */
        $invitation = WorkspaceInvitation::with(['workspace', 'invitedBy', 'branch', 'customRole'])
            ->where('token', $token)
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['Tautan undangan tidak valid atau tidak ditemukan.'],
            ]);
        }

        if ($invitation->status !== 'PENDING' || $invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['Tautan undangan telah kedaluwarsa atau tidak lagi berlaku.'],
            ]);
        }

        return $invitation;
    }

    /**
     * terima undangan tim dan jadikan pengguna sebagai member aktif workspace
     *
     * @return array{member: WorkspaceMember, user: User, token: string}
     */
    public function acceptInvitation(
        string $token,
        ?User $authenticatedUser = null,
        ?string $name = null,
        ?string $password = null
    ): array {
        $invitation = $this->getInvitationByToken($token);

        return DB::transaction(function () use ($invitation, $authenticatedUser, $name, $password): array {
            $user = $authenticatedUser;

            if (! $user) {
                $user = User::where('email', $invitation->email)->first();

                if (! $user) {
                    if (empty($password)) {
                        throw ValidationException::withMessages([
                            'password' => ['Kata sandi wajib diisi untuk membuat akun baru.'],
                        ]);
                    }

                    $user = User::create([
                        'name' => $name ? trim($name) : explode('@', $invitation->email)[0],
                        'email' => $invitation->email,
                        'email_verified_at' => Carbon::now(),
                        'password' => Hash::make($password),
                        'subscription_status' => 'ACTIVE',
                        'max_workspaces' => 1,
                    ]);
                }
            }

            // pastikan email pengguna cocok dengan email undangan
            if (strtolower($user->email) !== strtolower($invitation->email)) {
                throw ValidationException::withMessages([
                    'email' => ['Alamat email akun Anda tidak sesuai dengan alamat email tujuan undangan ini.'],
                ]);
            }

            // buat atau perbarui keanggotaan workspace
            /** @var WorkspaceMember $member */
            $member = WorkspaceMember::withoutGlobalScopes()->updateOrCreate(
                [
                    'workspace_id' => $invitation->workspace_id,
                    'user_id' => $user->id,
                ],
                [
                    'branch_id' => $invitation->branch_id,
                    'role_id' => $invitation->role_id,
                    'job_title' => $invitation->job_title,
                    'role' => $invitation->role,
                    'base_salary' => $invitation->base_salary,
                    'is_active' => true,
                ]
            );

            // tandai undangan telah diterima
            $invitation->update(['status' => 'ACCEPTED']);

            // terbitkan token akses baru untuk portal
            $tokenResult = $user->createToken('portal_invitation_accept_' . Str::random(6))->plainTextToken;

            return [
                'member' => $member,
                'user' => $user,
                'token' => $tokenResult,
            ];
        });
    }

    /**
     * tolak undangan tim
     */
    public function rejectInvitation(string $token): bool
    {
        $invitation = $this->getInvitationByToken($token);
        $invitation->update(['status' => 'REJECTED']);

        return true;
    }
}
