<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchController
{
    /**
     * List all branches in the active workspace with their settings and terminals.
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $branches = Branch::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['settings', 'terminals'])
            ->get()
            ->map(function (Branch $b) {
                $setting = $b->settings;
                return [
                    'id' => $b->id,
                    'workspace_id' => $b->workspace_id,
                    'name' => $b->name,
                    'lat' => (float) $b->lat,
                    'lng' => (float) $b->lng,
                    'radius_meters' => (int) $b->radius_meters,
                    'late_penalty_per_minute' => $setting ? (float) $setting->late_penalty_per_minute : 1000.0,
                    'overtime_pay_per_hour' => $setting ? (float) $setting->overtime_pay_per_hour : 20000.0,
                    'min_overtime_threshold_minutes' => $setting ? (int) $setting->min_overtime_threshold_minutes : 30,
                    'terminals_count' => $b->terminals->count(),
                    'created_at' => $b->created_at?->toIso8601String(),
                ];
            });

        return new JsonResponse([
            'message' => 'Daftar cabang berhasil dimuat.',
            'data' => $branches,
        ], Response::HTTP_OK);
    }

    /**
     * Update branch details & geofence settings (Owner / Admin only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $branch = Branch::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'radius_meters' => 'sometimes|integer|min:10|max:1000',
            'late_penalty_per_minute' => 'sometimes|numeric|min:0',
            'overtime_pay_per_hour' => 'sometimes|numeric|min:0',
            'min_overtime_threshold_minutes' => 'sometimes|integer|min:0|max:180',
        ]);

        if (isset($validated['name'])) {
            $branch->name = $validated['name'];
        }
        if (isset($validated['lat'])) {
            $branch->lat = $validated['lat'];
        }
        if (isset($validated['lng'])) {
            $branch->lng = $validated['lng'];
        }
        if (isset($validated['radius_meters'])) {
            $branch->radius_meters = $validated['radius_meters'];
        }
        $branch->save();

        $setting = BranchSetting::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceId,
                'branch_id' => $branch->id,
            ],
            [
                'late_penalty_per_minute' => 1000.0,
                'overtime_pay_per_hour' => 20000.0,
                'min_overtime_threshold_minutes' => 30,
            ]
        );

        if (isset($validated['late_penalty_per_minute'])) {
            $setting->late_penalty_per_minute = $validated['late_penalty_per_minute'];
        }
        if (isset($validated['overtime_pay_per_hour'])) {
            $setting->overtime_pay_per_hour = $validated['overtime_pay_per_hour'];
        }
        if (isset($validated['min_overtime_threshold_minutes'])) {
            $setting->min_overtime_threshold_minutes = $validated['min_overtime_threshold_minutes'];
        }
        $setting->save();

        return new JsonResponse([
            'message' => 'Pengaturan cabang berhasil diperbarui.',
            'data' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'lat' => (float) $branch->lat,
                'lng' => (float) $branch->lng,
                'radius_meters' => (int) $branch->radius_meters,
                'late_penalty_per_minute' => (float) $setting->late_penalty_per_minute,
                'overtime_pay_per_hour' => (float) $setting->overtime_pay_per_hour,
                'min_overtime_threshold_minutes' => (int) $setting->min_overtime_threshold_minutes,
            ],
        ], Response::HTTP_OK);
    }
}
