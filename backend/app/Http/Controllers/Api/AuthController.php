<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
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
