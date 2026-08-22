<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WorkspaceMember;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * auth user pakai email dan password, lalu terbitkan token Sanctum
     *
     * @return array{token: string, user: User, workspaces: array<int, mixed>}
     */
    public function authenticate(string $email, string $password, ?string $deviceName = null): array
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kombinasi email dan kata sandi tidak cocok.'],
            ]);
        }

        $tokenName = $deviceName ?: 'web_portal_' . Str::random(8);
        $token = $user->createToken($tokenName)->plainTextToken;

        $memberships = WorkspaceMember::withoutGlobalScopes()
            ->with(['workspace', 'branch'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $workspaces = $memberships->map(function (WorkspaceMember $membership): array {
            return [
                'workspace_id' => $membership->workspace_id,
                'workspace_name' => $membership->workspace?->name,
                'workspace_slug' => $membership->workspace?->slug,
                'role' => $membership->role,
                'branch_id' => $membership->branch_id,
                'branch_name' => $membership->branch?->name,
            ];
        })->toArray();

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
        $memberships = WorkspaceMember::withoutGlobalScopes()
            ->with(['workspace', 'branch'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $workspaces = $memberships->map(function (WorkspaceMember $membership): array {
            return [
                'workspace_id' => $membership->workspace_id,
                'workspace_name' => $membership->workspace?->name,
                'workspace_slug' => $membership->workspace?->slug,
                'role' => $membership->role,
                'branch_id' => $membership->branch_id,
                'branch_name' => $membership->branch?->name,
            ];
        })->toArray();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subscription_status' => $user->subscription_status,
            'subscription_expires_at' => $user->subscription_expires_at?->toIso8601String(),
            'max_workspaces' => $user->max_workspaces,
            'workspaces' => $workspaces,
        ];
    }

    /**
     * buat token reset password dan kirimkan email notifikasi
     */
    public function sendPasswordResetLink(string $email): void
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        $resetUrl = config('app.url') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        $user->notify(new ResetPasswordNotification($token, $resetUrl));
    }

    /**
     * reset password user dengan token yang sah
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'token' => ['Token pemulihan kata sandi tidak valid atau telah kedaluwarsa.'],
            ]);
        }

        // batas kedaluwarsa token 60 menit
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
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
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Pengguna dengan email ini tidak ditemukan.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        // hapus token reset dan cabut seluruh sesi lama buat keamanan
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $user->tokens()->delete();

        return true;
    }

    /**
     * verifikasi password owner / admin buat master unlock kiosk di POS
     */
    public function verifyPosMasterUnlock(string $workspaceId, string $email, string $password): bool
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return false;
        }

        // pastikan user punya role OWNER atau ADMIN di workspace ini
        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('role', ['OWNER', 'ADMIN'])
            ->first();

        return $member !== null;
    }
}
