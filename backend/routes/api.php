<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CashAdvanceController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\PosSecurityController;
use App\Http\Controllers\Api\SalesAnalyticsController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ShiftTemplateController;
use App\Http\Controllers\Api\SuperadminInvoiceController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| rute REST API
|--------------------------------------------------------------------------
|
| endpoint API publik, portal user authenticated, multi-tenancy context scoping,
| billing SaaS, presensi karyawan PWA, roster shift, kasbon staf, payroll, dan kasir POS
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
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // authenticated portal users
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'updatePassword']);
            Route::put('/bank-account', [AuthController::class, 'updateBankAccount']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/workspaces', [WorkspaceController::class, 'store']);
        });
    });

    // endpoint pembuatan workspace oleh user yang terotentikasi
    Route::middleware('auth:sanctum')->post('/workspaces', [WorkspaceController::class, 'store']);

    // endpoint publik penerimaan undangan tim workspace
    Route::prefix('invitations')->group(function (): void {
        Route::get('/{token}', [InvitationController::class, 'show']);
        Route::post('/{token}/accept', [InvitationController::class, 'accept']);
        Route::post('/{token}/reject', [InvitationController::class, 'reject']);
    });

    // billing saas user portal
    Route::middleware('auth:sanctum')->prefix('billing')->group(function (): void {
        Route::get('/plans', [BillingController::class, 'plans']);
        Route::post('/invoices', [BillingController::class, 'createInvoice']);
        Route::get('/invoices', [BillingController::class, 'myInvoices']);
        Route::post('/invoices/{id}/proof', [BillingController::class, 'submitProof']);
    });

    // endpoint superadmin
    Route::prefix('superadmin')->group(function (): void {
        Route::post('/auth/login', [\App\Http\Controllers\Api\SuperadminController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/auth/me', [\App\Http\Controllers\Api\SuperadminController::class, 'me']);
            Route::post('/auth/logout', [\App\Http\Controllers\Api\SuperadminController::class, 'logout']);

            Route::get('/invoices', [\App\Http\Controllers\Api\SuperadminController::class, 'invoices']);
            Route::post('/invoices/{id}/verify', [\App\Http\Controllers\Api\SuperadminController::class, 'verifyInvoice']);

            Route::get('/metrics', [\App\Http\Controllers\Api\SuperadminController::class, 'metrics']);

            Route::get('/tenants', [\App\Http\Controllers\Api\SuperadminController::class, 'tenants']);
            Route::post('/tenants/{id}/status', [\App\Http\Controllers\Api\SuperadminController::class, 'updateTenantStatus']);
            Route::post('/tenants/{id}/extend', [\App\Http\Controllers\Api\SuperadminController::class, 'extendTenantSubscription']);

            Route::get('/plans', [\App\Http\Controllers\Api\SuperadminController::class, 'plans']);
        });
    });

    // endpoint scoping workspace multi-tenant (sanctum + resolveworkspacecontext + checksubscriptionstatus)
    Route::middleware(['auth:sanctum', 'workspace.context', 'subscription.status'])->group(function (): void {

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

        // manajemen cabang outlet
        Route::get('/branches', [\App\Http\Controllers\Api\BranchController::class, 'index']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->put('/branches/{id}', [\App\Http\Controllers\Api\BranchController::class, 'update']);

        // katalog produk & kategori web portal
        Route::get('/products', [\App\Http\Controllers\Api\ProductCatalogController::class, 'products']);
        Route::get('/categories', [\App\Http\Controllers\Api\ProductCatalogController::class, 'categories']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->post('/products', [\App\Http\Controllers\Api\ProductCatalogController::class, 'storeProduct']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->put('/products/{id}', [\App\Http\Controllers\Api\ProductCatalogController::class, 'updateProduct']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->delete('/products/{id}', [\App\Http\Controllers\Api\ProductCatalogController::class, 'deleteProduct']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->post('/categories', [\App\Http\Controllers\Api\ProductCatalogController::class, 'storeCategory']);
        Route::middleware('role:OWNER,ADMIN,MANAGER')->delete('/categories/{id}', [\App\Http\Controllers\Api\ProductCatalogController::class, 'deleteCategory']);

        // presensi mobile PWA
        Route::prefix('attendances')->group(function (): void {
            Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        });

        // manajemen roster shift, templates & pengajuan tukar shift staf
        Route::prefix('shifts')->group(function (): void {
            Route::get('/templates', [ShiftTemplateController::class, 'index']);
            Route::middleware('role:OWNER,ADMIN,MANAGER')->post('/templates', [ShiftTemplateController::class, 'store']);
            Route::middleware('role:OWNER,ADMIN,MANAGER')->delete('/templates/{id}', [ShiftTemplateController::class, 'destroy']);

            Route::get('/roster', [ShiftController::class, 'roster']);
            Route::post('/swap-requests', [ShiftController::class, 'requestSwap']);
            Route::middleware('role:OWNER,ADMIN,MANAGER')->post('/assign', [ShiftController::class, 'assign']);
        });

        // manajemen kasbon staf
        Route::prefix('cash-advances')->group(function (): void {
            Route::post('/', [CashAdvanceController::class, 'create']);
            Route::get('/my', [CashAdvanceController::class, 'my']);
        });

        // slip gaji digital staf
        Route::get('/payroll/my-slip', [PayrollController::class, 'mySlip']);

        // endpoint administrasi khusus role OWNER dan ADMIN/MANAGER
        Route::middleware('role:OWNER,ADMIN,MANAGER')->prefix('admin')->group(function (): void {
            Route::get('/attendances/wall-of-faces', [AttendanceController::class, 'wallOfFaces']);
            Route::get('/analytics/sales', [SalesAnalyticsController::class, 'sales']);

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

            Route::prefix('payroll')->group(function (): void {
                Route::get('/preview', [PayrollController::class, 'preview']);
                Route::post('/disburse', [PayrollController::class, 'disburse']);
                Route::get('/export-csv', [PayrollController::class, 'exportCsv']);
            });

            Route::prefix('members')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\MemberController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Api\MemberController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\MemberController::class, 'update']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\MemberController::class, 'destroy']);
            });

            Route::prefix('invitations')->group(function (): void {
                Route::get('/', [InvitationController::class, 'index']);
                Route::post('/', [InvitationController::class, 'store']);
                Route::delete('/{id}', [InvitationController::class, 'destroy']);
                Route::post('/{id}/resend', [InvitationController::class, 'resend']);
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
