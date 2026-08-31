<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\RawMaterial;
use App\Models\StockAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RawMaterialController
{
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $materials = RawMaterial::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn (RawMaterial $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category_id' => $m->category_id ?? '',
                'category_name' => $m->category?->name ?? 'Umum',
                'current_stock' => (float) $m->current_stock,
                'min_stock_alert' => (float) $m->min_stock_alert,
                'unit' => $m->unit,
                'last_adjusted_at' => $m->last_adjusted_at?->format('Y-m-d H:i') ?? $m->updated_at?->format('Y-m-d H:i'),
            ]);

        return new JsonResponse([
            'message' => 'Daftar bahan baku berhasil dimuat.',
            'data' => $materials,
        ], Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'string'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
        ]);

        $material = RawMaterial::create([
            'workspace_id' => $workspaceId,
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'current_stock' => (float) $validated['current_stock'],
            'min_stock_alert' => isset($validated['min_stock_alert']) ? (float) $validated['min_stock_alert'] : 5.0,
            'unit' => strtolower($validated['unit']),
            'last_adjusted_at' => now(),
        ]);

        return new JsonResponse([
            'message' => 'Bahan baku berhasil ditambahkan.',
            'data' => [
                'id' => $material->id,
                'name' => $material->name,
                'category_id' => $material->category_id ?? '',
                'category_name' => $material->category?->name ?? 'Umum',
                'current_stock' => (float) $material->current_stock,
                'min_stock_alert' => (float) $material->min_stock_alert,
                'unit' => $material->unit,
                'last_adjusted_at' => $material->last_adjusted_at?->format('Y-m-d H:i'),
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $material = RawMaterial::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $material->delete();

        return new JsonResponse([
            'message' => 'Bahan baku berhasil dihapus.',
        ], Response::HTTP_OK);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $logs = StockAdjustment::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with('material')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (StockAdjustment $a) => [
                'id' => $a->id,
                'material_id' => $a->material_id,
                'material_name' => $a->material?->name ?? 'Bahan Baku',
                'reason' => $a->reason,
                'adjusted_amount' => (float) $a->adjusted_amount,
                'resulting_stock' => (float) $a->resulting_stock,
                'notes' => $a->notes,
                'performed_by' => $a->performed_by ?? 'Staf Kasir',
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return new JsonResponse([
            'message' => 'Daftar log penyesuaian stok berhasil dimuat.',
            'data' => $logs,
        ], Response::HTTP_OK);
    }

    public function storeAdjustment(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'material_id' => ['required', 'string', 'exists:raw_materials,id'],
            'new_stock' => ['nullable', 'numeric', 'min:0'],
            'delta_stock' => ['nullable', 'numeric'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'performed_by' => ['nullable', 'string'],
        ]);

        $material = RawMaterial::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $validated['material_id'])
            ->firstOrFail();

        $prevStock = (float) $material->current_stock;

        if (isset($validated['new_stock'])) {
            $newStock = (float) $validated['new_stock'];
            $delta = $newStock - $prevStock;
        } else {
            $delta = (float) ($validated['delta_stock'] ?? 0);
            $newStock = max(0, $prevStock + $delta);
        }

        $material->current_stock = $newStock;
        $material->last_adjusted_at = now();
        $material->save();

        $log = StockAdjustment::create([
            'workspace_id' => $workspaceId,
            'material_id' => $material->id,
            'reason' => $validated['reason'],
            'adjusted_amount' => $delta,
            'resulting_stock' => $newStock,
            'notes' => $validated['notes'] ?? null,
            'performed_by' => $validated['performed_by'] ?? 'Staf Kasir',
        ]);

        return new JsonResponse([
            'message' => 'Penyesuaian stok berhasil disimpan.',
            'data' => [
                'log' => [
                    'id' => $log->id,
                    'material_id' => $log->material_id,
                    'material_name' => $material->name,
                    'reason' => $log->reason,
                    'adjusted_amount' => (float) $log->adjusted_amount,
                    'resulting_stock' => (float) $log->resulting_stock,
                    'notes' => $log->notes,
                    'performed_by' => $log->performed_by,
                    'created_at' => $log->created_at?->toIso8601String(),
                ],
                'material' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'category_id' => $material->category_id ?? '',
                    'category_name' => $material->category?->name ?? 'Umum',
                    'current_stock' => (float) $material->current_stock,
                    'min_stock_alert' => (float) $material->min_stock_alert,
                    'unit' => $material->unit,
                    'last_adjusted_at' => $material->last_adjusted_at?->format('Y-m-d H:i'),
                ],
            ],
        ], Response::HTTP_CREATED);
    }
}
