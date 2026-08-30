<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\SalesAnalyticsResource;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalesAnalyticsController
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    /**
     * agregasi analitik penjualan dinamis dari database untuk OWNER dan MANAGER
     */
    public function sales(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $period = (string) $request->query('period', 'day');
        $branchId = $request->query('branch_id') ? (string) $request->query('branch_id') : null;

        $analyticsData = $this->analyticsService->getSalesAnalytics(
            workspaceId: $workspaceId,
            period: $period,
            branchId: $branchId,
        );

        return new JsonResponse([
            'message' => 'Data analitik penjualan berhasil dimuat.',
            'data' => (new SalesAnalyticsResource($analyticsData))->resolve(),
        ], Response::HTTP_OK);
    }
}
