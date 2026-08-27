<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ResetPasswordMailable;
use App\Mail\VerifyEmailMailable;
use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * registrasi pemilik bisnis / user baru secara mandiri
     *
     * @return array{token: string, user: User, workspaces: array<int, mixed>}
     */
    public function register(string $name, string $email, string $password, ?string $workspaceName = null): array
    {
        $normalizedEmail = strtolower(trim($email));

        return DB::transaction(function () use ($name, $normalizedEmail, $password, $workspaceName): array {
            $verificationToken = Str::random(64);

            /** @var User $user */
            $user = User::create([
                'name' => trim($name),
                'email' => $normalizedEmail,
                'password' => Hash::make($password),
                'email_verification_token' => $verificationToken,
                'subscription_status' => 'TRIAL',
                'subscription_expires_at' => Carbon::now()->addDays(14),
                'max_workspaces' => 1,
            ]);

            $workspaces = [];

            // buat workspace dan cabang default jika nama brand disertakan
            if ($workspaceName && trim($workspaceName) !== '') {
                $trimmedWsName = trim($workspaceName);
                $slug = Str::slug($trimmedWsName) . '-' . Str::random(5);

                /** @var Workspace $workspace */
                $workspace = Workspace::create([
                    'name' => $trimmedWsName,
                    'slug' => $slug,
                    'owner_user_id' => $user->id,
                    'status' => 'ACTIVE',
                ]);

                /** @var Branch $branch */
                $branch = Branch::create([
                    'workspace_id' => $workspace->id,
                    'name' => 'Cabang Utama',
                    'lat' => -7.78290000,
                    'lng' => 110.36710000,
                    'radius_meters' => 50,
                ]);

                /** @var WorkspaceMember $member */
                $member = WorkspaceMember::withoutGlobalScopes()->create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'job_title' => 'Pemilik Usaha',
                    'role' => 'OWNER',
                    'base_salary' => 0.00,
                    'is_active' => true,
                ]);

                $workspaces[] = [
                    'workspace_id' => $workspace->id,
                    'workspace_name' => $workspace->name,
                    'workspace_slug' => $workspace->slug,
                    'role' => $member->role,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                ];
            }

            // kirimkan email verifikasi akun baru
            $verificationUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/verify-email?token=' . $verificationToken;
            Mail::to($normalizedEmail)->send(new VerifyEmailMailable($user, $verificationToken, $verificationUrl));

            $token = $user->createToken('web_portal_' . Str::random(8))->plainTextToken;

            return [
                'token' => $token,
                'user' => $user,
                'workspaces' => $workspaces,
            ];
        });
    }

    /**
     * verifikasi kepemilikan email pengguna melalui token
     */
    public function verifyEmail(string $token): User
    {
        /** @var User|null $user */
        $user = User::where('email_verification_token', $token)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['Token verifikasi email tidak valid atau telah digunakan.'],
            ]);
        }

        $user->update([
            'email_verified_at' => Carbon::now(),
            'email_verification_token' => null,
        ]);

        return $user;
    }

    /**
     * kirim ulang email verifikasi jika belum terverifikasi
     */
    public function resendEmailVerification(string $email): void
    {
        $normalizedEmail = strtolower(trim($email));
        /** @var User|null $user */
        $user = User::where('email', $normalizedEmail)->first();

        if (! $user) {
            return;
        }

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email ini sudah terverifikasi sebelumnya.'],
            ]);
        }

        $token = Str::random(64);
        $user->update(['email_verification_token' => $token]);

        $verificationUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/verify-email?token=' . $token;
        Mail::to($normalizedEmail)->send(new VerifyEmailMailable($user, $token, $verificationUrl));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSerializedUserWorkspaces(string $userId): array
    {
        return WorkspaceMember::withoutGlobalScopes()
            ->with(['workspace', 'branch', 'customRole.permissions'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get()
            ->map(function (WorkspaceMember $m): array {
                $isOwner = $m->role === 'OWNER';
                $permissions = $isOwner ? ['*'] : ($m->customRole ? $m->customRole->permissions->pluck('permission')->toArray() : []);
                if (! $isOwner && empty($permissions) && $m->role === 'MANAGER') {
                    $permissions = [
                        'catalog.view',
                        'catalog.manage',
                        'inventory.view',
                        'inventory.adjust',
                        'attendance.view_all',
                        'shifts.manage',
                        'shifts.approve_swap',
                        'sales.view_analytics',
                        'cash_advance.approve',
                        'members.view',
                        'pos.manage_terminals',
                        'pos.void_order',
                        'pos.apply_discount',
                    ];
                }

                return [
                    'workspace_id' => $m->workspace_id,
                    'workspace_name' => $m->workspace?->name,
                    'workspace_slug' => $m->workspace?->slug,
                    'role' => $m->role,
                    'job_title' => $m->job_title,
                    'permissions' => $permissions,
                    'branch_id' => $m->branch_id,
                    'branch_name' => $m->branch?->name,
                ];
            })
            ->toArray();
    }

    /**
     * auth user pakai email dan password, lalu terbitkan token Sanctum
     *
     * @return array{token: string, user: User, workspaces: array<int, mixed>}
     */
    public function authenticate(string $email, string $password, ?string $deviceName = null): array
    {
        /** @var User|null $user */
        $user = User::where('email', strtolower(trim($email)))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kombinasi email dan kata sandi tidak cocok.'],
            ]);
        }

        $tokenName = $deviceName ?: 'web_portal_' . Str::random(8);
        $token = $user->createToken($tokenName)->plainTextToken;

        $workspaces = $this->getSerializedUserWorkspaces((string) $user->id);

        return [
            'token' => $token,
            'user' => $user,
            'workspaces' => $workspaces,
        ];
    }

    /**
     * ambil data profil lengkap dan daftar workspace yang dapat diakses user
     *
     * @return array<string, mixed>
     */
    public function getUserProfile(User $user): array
    {
        $workspaces = $this->getSerializedUserWorkspaces((string) $user->id);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'bank_name' => $user->bank_name,
            'bank_account_number' => $user->bank_account_number,
            'bank_account_holder' => $user->bank_account_holder,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'subscription_status' => $user->subscription_status,
            'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
            'max_workspaces' => $user->max_workspaces,
            'workspaces' => $workspaces,
        ];
    }

    /**
     * perbarui data rekening bank mandiri pengguna untuk pencairan payroll
     */
    public function updateBankAccount(User $user, string $bankName, string $accountNumber, string $accountHolder): User
    {
        $user->update([
            'bank_name' => trim($bankName),
            'bank_account_number' => trim($accountNumber),
            'bank_account_holder' => trim($accountHolder),
        ]);

        return $user;
    }

    /**
     * perbarui kata sandi user yang sedang login
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi lama yang Anda masukkan tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * perbarui profil nama user
     */
    public function updateProfile(User $user, string $name): User
    {
        $user->update([
            'name' => trim($name),
        ]);

        return $user;
    }

    /**
     * buat token reset password dan kirimkan email notifikasi
     */
    public function sendPasswordResetLink(string $email): void
    {
        $normalizedEmail = strtolower(trim($email));
        /** @var User|null $user */
        $user = User::where('email', $normalizedEmail)->first();
        if (! $user) {
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $normalizedEmail],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        $resetUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5174'), '/') . '/reset-password?token=' . $token . '&email=' . urlencode($normalizedEmail);

        $user->notify(new \App\Notifications\ResetPasswordNotification($token, $resetUrl));
        Mail::to($normalizedEmail)->send(new ResetPasswordMailable($user, $token, $resetUrl));
    }

    /**
     * reset password user dengan token yang sah
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $record = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'token' => ['Token pemulihan kata sandi tidak valid atau telah kedaluwarsa.'],
            ]);
        }

        // batas kedaluwarsa token 60 menit
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
            throw ValidationException::withMessages([
                'token' => ['Token pemulihan kata sandi telah kedaluwarsa. Silakan ajukan permohonan baru.'],
            ]);
        }

        if (! Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Token pemulihan kata sandi tidak cocok.'],
            ]);
        }

        /** @var User|null $user */
        $user = User::where('email', $normalizedEmail)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Pengguna dengan email ini tidak ditemukan.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        // hapus token reset dan cabut seluruh sesi lama buat keamanan
        DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
        $user->tokens()->delete();

        return true;
    }

    /**
     * verifikasi password owner / admin buat master unlock kiosk di POS
     */
    public function verifyPosMasterUnlock(string $workspaceId, string $email, string $password): bool
    {
        /** @var User|null $user */
        $user = User::where('email', strtolower(trim($email)))->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return false;
        }

        // pastikan user punya role OWNER, ADMIN, MANAGER, atau permission pos.manage_terminals / pos.void_order
        $members = WorkspaceMember::withoutGlobalScopes()
            ->with('customRole.permissions')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($members as $member) {
            if (
                in_array($member->role, ['OWNER', 'ADMIN', 'MANAGER'], true)
                || $member->hasPermission(['pos.manage_terminals', 'pos.void_order'])
            ) {
                return true;
            }
        }

        return false;
    }
}
