<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PosSecurityController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute REST API v1 Précis
|--------------------------------------------------------------------------
|
| Definisi seluruh endpoint API publik, portal pengguna terautentikasi,
| multi-tenancy context scoping, dan terminal kasir POS.
|
*/

Route::prefix('v1')->group(function (): void {

    // 1. Endpoint Pemeriksaan Kesehatan Sistem (Health Check)
    Route::get('/health', function (): JsonResponse {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'Precis Backend API',
        ]);
    });

    // 2. Endpoint Otentikasi Pengguna & Portal
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // Rute portal terotentikasi (Laravel Sanctum)
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // 3. Endpoint Scoping Workspace Multi-Tenant (Sanctum + ResolveWorkspaceContext)
    Route::middleware(['auth:sanctum', 'workspace.context'])->prefix('workspace')->group(function (): void {
        Route::get('/context', function (Request $request): JsonResponse {
            $workspace = $request->attributes->get('current_workspace');
            $member = $request->attributes->get('current_member');

            return response()->json([
                'workspace' => $workspace,
                'member' => $member,
            ]);
        });

        // Sub-grup khusus peran OWNER dan ADMIN
        Route::middleware('role:OWNER,ADMIN')->get('/admin-only', function (Request $request): JsonResponse {
            return response()->json([
                'message' => 'Akses halaman admin diizinkan.',
                'member_role' => $request->attributes->get('current_member')?->role,
            ]);
        });
    });

    // 4. Endpoint Kiosk Terminal POS (DeviceTokenAuth)
    Route::middleware('pos.device')->prefix('pos')->group(function (): void {
        Route::post('/master-unlock', [PosSecurityController::class, 'masterUnlock']);

        Route::get('/terminal-info', function (Request $request): JsonResponse {
            $terminal = $request->attributes->get('current_pos_terminal');

            return response()->json([
                'terminal_id' => $terminal->id,
                'terminal_name' => $terminal->terminal_name,
                'workspace_id' => $terminal->workspace_id,
                'branch_id' => $terminal->branch_id,
                'branch_name' => $terminal->branch?->name,
            ]);
        });
    });
});
