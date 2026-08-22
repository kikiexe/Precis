<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pos\PosMasterUnlockRequest;
use App\Models\PosTerminal;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosSecurityController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    /**
     * verifikasi password owner/admin untuk membuka kunci sementara pengaturan pada POS
     */
    public function masterUnlock(PosMasterUnlockRequest $request): JsonResponse
    {
        /** @var PosTerminal $terminal */
        $terminal = $request->attributes->get('current_pos_terminal');

        $isValid = $this->authService->verifyPosMasterUnlock(
            workspaceId: $terminal->workspace_id,
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        if (! $isValid) {
            return new JsonResponse([
                'message' => 'Otorisasi gagal. Kata sandi master salah atau akun tidak memiliki peran OWNER/ADMIN pada outlet ini.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'message' => 'Otorisasi master berhasil. Kiosk unlocked.',
            'data' => [
                'unlocked_at' => now()->toIso8601String(),
                'terminal_id' => $terminal->id,
                'branch_id' => $terminal->branch_id,
                'workspace_id' => $terminal->workspace_id,
            ],
        ], Response::HTTP_OK);
    }
}
