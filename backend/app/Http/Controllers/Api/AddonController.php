<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Addon;
use App\Models\AddonCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AddonController
{
    /**
     * ambil daftar seluruh kategori add-on beserta opsi itemnya
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $categories = AddonCategory::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['addons', 'products'])
            ->orderBy('name')
            ->get()
            ->map(function (AddonCategory $cat): array {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'selection_type' => $cat->selection_type,
                    'is_required' => (bool) $cat->is_required,
                    'min_selection' => (int) $cat->min_selection,
                    'max_selection' => (int) $cat->max_selection,
                    'product_ids' => $cat->products->pluck('id')->toArray(),
                    'addons' => $cat->addons->map(function (Addon $a): array {
                        return [
                            'id' => $a->id,
                            'addon_category_id' => $a->addon_category_id,
                            'name' => $a->name,
                            'price' => (float) $a->price,
                            'is_active' => (bool) $a->is_active,
                        ];
                    })->toArray(),
                ];
            });

        return new JsonResponse([
            'message' => 'Daftar kategori add-on berhasil dimuat.',
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    /**
     * simpan kategori add-on baru beserta item dan relasi produk
     */
    public function store(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'selection_type' => 'nullable|string|in:SINGLE,MULTIPLE',
            'is_required' => 'nullable|boolean',
            'min_selection' => 'nullable|integer|min:0',
            'max_selection' => 'nullable|integer|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'uuid|exists:products,id',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string|max:100',
            'addons.*.price' => 'required|numeric|min:0',
            'addons.*.is_active' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($workspaceId, $validated): JsonResponse {
            $category = AddonCategory::create([
                'workspace_id' => $workspaceId,
                'name' => $validated['name'],
                'selection_type' => $validated['selection_type'] ?? 'MULTIPLE',
                'is_required' => $validated['is_required'] ?? false,
                'min_selection' => $validated['min_selection'] ?? 0,
                'max_selection' => $validated['max_selection'] ?? 0,
            ]);

            if (! empty($validated['product_ids'])) {
                $category->products()->sync($validated['product_ids']);
            }

            if (! empty($validated['addons'])) {
                foreach ($validated['addons'] as $addonData) {
                    Addon::create([
                        'workspace_id' => $workspaceId,
                        'addon_category_id' => $category->id,
                        'name' => $addonData['name'],
                        'price' => $addonData['price'],
                        'is_active' => $addonData['is_active'] ?? true,
                    ]);
                }
            }

            $category->load(['addons', 'products']);

            return new JsonResponse([
                'message' => 'Kategori add-on berhasil dibuat.',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'selection_type' => $category->selection_type,
                    'is_required' => (bool) $category->is_required,
                    'min_selection' => (int) $category->min_selection,
                    'max_selection' => (int) $category->max_selection,
                    'product_ids' => $category->products->pluck('id')->toArray(),
                    'addons' => $category->addons->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'price' => (float) $a->price,
                        'is_active' => (bool) $a->is_active,
                    ])->toArray(),
                ],
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * ambil detail kategori add-on
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $category = AddonCategory::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->with(['addons', 'products'])
            ->firstOrFail();

        return new JsonResponse([
            'message' => 'Detail kategori add-on berhasil dimuat.',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'selection_type' => $category->selection_type,
                'is_required' => (bool) $category->is_required,
                'min_selection' => (int) $category->min_selection,
                'max_selection' => (int) $category->max_selection,
                'product_ids' => $category->products->pluck('id')->toArray(),
                'addons' => $category->addons->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'price' => (float) $a->price,
                    'is_active' => (bool) $a->is_active,
                ])->toArray(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * perbarui data kategori add-on
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $category = AddonCategory::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'selection_type' => 'sometimes|string|in:SINGLE,MULTIPLE',
            'is_required' => 'sometimes|boolean',
            'min_selection' => 'sometimes|integer|min:0',
            'max_selection' => 'sometimes|integer|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'uuid|exists:products,id',
        ]);

        return DB::transaction(function () use ($category, $validated): JsonResponse {
            $category->update(array_filter([
                'name' => $validated['name'] ?? null,
                'selection_type' => $validated['selection_type'] ?? null,
                'is_required' => $validated['is_required'] ?? null,
                'min_selection' => $validated['min_selection'] ?? null,
                'max_selection' => $validated['max_selection'] ?? null,
            ], fn ($v) => $v !== null));

            if (isset($validated['product_ids'])) {
                $category->products()->sync($validated['product_ids']);
            }

            $category->load(['addons', 'products']);

            return new JsonResponse([
                'message' => 'Kategori add-on berhasil diperbarui.',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'selection_type' => $category->selection_type,
                    'is_required' => (bool) $category->is_required,
                    'min_selection' => (int) $category->min_selection,
                    'max_selection' => (int) $category->max_selection,
                    'product_ids' => $category->products->pluck('id')->toArray(),
                    'addons' => $category->addons->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'price' => (float) $a->price,
                        'is_active' => (bool) $a->is_active,
                    ])->toArray(),
                ],
            ], Response::HTTP_OK);
        });
    }

    /**
     * hapus kategori add-on
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $category = AddonCategory::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $category->delete();

        return new JsonResponse([
            'message' => 'Kategori add-on berhasil dihapus.',
        ], Response::HTTP_OK);
    }

    /**
     * tambah opsi add-on pada suatu kategori
     */
    public function storeAddon(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'addon_category_id' => 'required|uuid|exists:addon_categories,id',
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category = AddonCategory::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $validated['addon_category_id'])
            ->firstOrFail();

        $addon = Addon::create([
            'workspace_id' => $workspaceId,
            'addon_category_id' => $category->id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return new JsonResponse([
            'message' => 'Item add-on berhasil ditambahkan.',
            'data' => [
                'id' => $addon->id,
                'addon_category_id' => $addon->addon_category_id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
                'is_active' => (bool) $addon->is_active,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * perbarui item add-on
     */
    public function updateAddon(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $addon = Addon::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $addon->update($validated);

        return new JsonResponse([
            'message' => 'Item add-on berhasil diperbarui.',
            'data' => [
                'id' => $addon->id,
                'addon_category_id' => $addon->addon_category_id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
                'is_active' => (bool) $addon->is_active,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * hapus item add-on
     */
    public function destroyAddon(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $addon = Addon::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $addon->delete();

        return new JsonResponse([
            'message' => 'Item add-on berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
