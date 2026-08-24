<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    /**
     * registrasi akun pemilik bisnis baru dan inisialisasi workspace
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            workspaceName: $request->validated('workspace_name'),
        );

        return new JsonResponse([
            'message' => 'Registrasi berhasil. Silakan periksa inbox email Anda untuk melakukan verifikasi akun.',
            'data' => [
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'email_verified_at' => $result['user']->email_verified_at?->toIso8601String(),
                    'subscription_status' => $result['user']->subscription_status,
                ],
                'workspaces' => $result['workspaces'],
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * verifikasi email akun pengguna melalui token
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->authService->verifyEmail(
            token: $request->validated('token'),
        );

        return new JsonResponse([
            'message' => 'Alamat email berhasil diverifikasi.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * kirim ulang email verifikasi
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $this->authService->resendEmailVerification(
            email: $request->validated('email'),
        );

        return new JsonResponse([
            'message' => 'Tautan verifikasi email baru telah dikirimkan ke alamat email Anda.',
        ], Response::HTTP_OK);
    }

    /**
     * authentikasi user dan pembuatan token sanctum
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->authenticate(
            email: $request->validated('email'),
            password: $request->validated('password'),
            deviceName: $request->validated('device_name'),
        );

        return new JsonResponse([
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'email_verified_at' => $result['user']->email_verified_at?->toIso8601String(),
                    'subscription_status' => $result['user']->subscription_status,
                ],
                'workspaces' => $result['workspaces'],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * ambil data profil user yang authenticated beserta workspace yang dapat diakses
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $this->authService->getUserProfile($user);

        return new JsonResponse([
            'message' => 'Profil pengguna berhasil dimuat.',
            'data' => $profile,
        ], Response::HTTP_OK);
    }

    /**
     * perbarui rekening bank pribadi pengguna
     */
    public function updateBankAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:50'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_holder' => ['required', 'string', 'max:150'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $updated = $this->authService->updateBankAccount(
            user: $user,
            bankName: $validated['bank_name'],
            accountNumber: $validated['bank_account_number'],
            accountHolder: $validated['bank_account_holder'],
        );

        return new JsonResponse([
            'message' => 'Rekening bank berhasil disimpan.',
            'data' => [
                'bank_name' => $updated->bank_name,
                'bank_account_number' => $updated->bank_account_number,
                'bank_account_holder' => $updated->bank_account_holder,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * perbarui nama profil user
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $updated = $this->authService->updateProfile($user, $validated['name']);

        return new JsonResponse([
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'email' => $updated->email,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * perbarui kata sandi user
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->authService->updatePassword(
            user: $user,
            currentPassword: $validated['current_password'],
            newPassword: $validated['password']
        );

        return new JsonResponse([
            'message' => 'Kata sandi berhasil diperbarui.',
        ], Response::HTTP_OK);
    }

    /**
     * revoke token akses aktif saat ini (logout)
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        return new JsonResponse([
            'message' => 'Logout berhasil. Sesi telah diakhiri.',
        ], Response::HTTP_OK);
    }

    /**
     * kirim link reset password ke email user
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink(
            email: $request->validated('email'),
        );

        return new JsonResponse([
            'message' => 'Tautan pemulihan kata sandi telah dikirim ke alamat email Anda.',
        ], Response::HTTP_OK);
    }

    /**
     * reset password user menggunakan token yang valid
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            email: $request->validated('email'),
            token: $request->validated('token'),
            newPassword: $request->validated('password'),
        );

        return new JsonResponse([
            'message' => 'Kata sandi berhasil diperbarui. Silakan login kembali.',
        ], Response::HTTP_OK);
    }
}
