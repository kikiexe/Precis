<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashAdvanceController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\PosSecurityController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| rute REST API
|--------------------------------------------------------------------------
|
| endpoint API publik, portal user authenticated, multi-tenancy context scoping,
| presensi karyawan PWA, roster shift, kasbon staf, dan kasir POS
|
*/

Route::prefix('v1')->group(function (): void {

    // endpoint health check
    Route::get('/health', function (): JsonResponse {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'Precis Backend API',
        ]);
    });

    // endpoint auth portal users
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // authenticated portal users
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // endpoint scoping workspace multi-tenant (sanctum + resolveworkspacecontext)
    Route::middleware(['auth:sanctum', 'workspace.context'])->group(function (): void {

        // rute konteks workspace
        Route::prefix('workspace')->group(function (): void {
            Route::get('/context', function (Request $request): JsonResponse {
                $workspace = $request->attributes->get('current_workspace');
                $member = $request->attributes->get('current_member');

                return response()->json([
                    'workspace' => $workspace,
                    'member' => $member,
                ]);
            });

            Route::middleware('role:OWNER,ADMIN')->get('/admin-only', function (Request $request): JsonResponse {
                return response()->json([
                    'message' => 'Akses halaman admin diizinkan.',
                    'member_role' => $request->attributes->get('current_member')?->role,
                ]);
            });
        });

        // media upload presigned URL (object storage)
        Route::post('/media/presign-upload', [MediaController::class, 'presignUpload']);

        // presensi mobile PWA
        Route::prefix('attendances')->group(function (): void {
            Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        });

        // manajemen roster shift & pengajuan tukar shift staf
        Route::prefix('shifts')->group(function (): void {
            Route::get('/roster', [ShiftController::class, 'roster']);
            Route::post('/swap-requests', [ShiftController::class, 'requestSwap']);
            Route::middleware('role:OWNER,ADMIN')->post('/assign', [ShiftController::class, 'assign']);
        });

        // manajemen kasbon staf
        Route::prefix('cash-advances')->group(function (): void {
            Route::post('/', [CashAdvanceController::class, 'create']);
            Route::get('/my', [CashAdvanceController::class, 'my']);
        });

        // endpoint administrasi khusus role OWNER dan ADMIN
        Route::middleware('role:OWNER,ADMIN')->prefix('admin')->group(function (): void {
            Route::get('/attendances/wall-of-faces', [AttendanceController::class, 'wallOfFaces']);

            Route::prefix('shifts')->group(function (): void {
                Route::get('/swap-requests', [ShiftController::class, 'pendingSwapRequests']);
                Route::post('/swap-requests/{id}/approve', [ShiftController::class, 'approveSwap']);
                Route::post('/swap-requests/{id}/reject', [ShiftController::class, 'rejectSwap']);
            });

            Route::prefix('cash-advances')->group(function (): void {
                Route::get('/', [CashAdvanceController::class, 'adminList']);
                Route::post('/{id}/approve', [CashAdvanceController::class, 'approve']);
                Route::post('/{id}/reject', [CashAdvanceController::class, 'reject']);
            });

            Route::get('/admin-only', function (Request $request): JsonResponse {
                return response()->json([
                    'message' => 'Akses halaman admin diizinkan.',
                    'member_role' => $request->attributes->get('current_member')?->role,
                ]);
            });
        });
    });

    // endpoint POS (devicetokenauth)
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

        // katalog produk offline & manajemen sesi kasir
        Route::get('/products', [PosController::class, 'products']);
        Route::post('/sessions/open', [PosController::class, 'openSession']);
        Route::post('/sessions/close', [PosController::class, 'closeSession']);
        Route::post('/orders/sync-batch', [PosController::class, 'syncBatch']);
    });
});
