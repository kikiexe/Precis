<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): JsonResponse {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'Precis Backend API',
        ]);
    });
});
