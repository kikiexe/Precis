<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductCatalogController
{
    /**
     * ambil daftar seluruh produk dalam workspace aktif
     */
    public function products(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $categoryId = $request->query('category_id');

        $query = Product::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with('category');

        if ($categoryId && $categoryId !== 'ALL') {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get()->map(function (Product $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name ?? 'Uncategorized',
                'price' => (float) $p->base_price,
                'base_price' => (float) $p->base_price,
                'description' => $p->description ?? '',
                'is_available' => (bool) $p->is_active,
                'is_active' => (bool) $p->is_active,
                'image_url' => $p->image_url,
            ];
        });

        return new JsonResponse([
            'message' => 'Daftar produk berhasil dimuat.',
            'data' => $products,
        ], Response::HTTP_OK);
    }

    /**
     * ambil daftar seluruh kategori dalam workspace aktif
     */
    public function categories(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $categories = Category::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function (Category $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'type' => 'MENU',
                    'item_count' => $c->products_count,
                ];
            });

        return new JsonResponse([
            'message' => 'Daftar kategori berhasil dimuat.',
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    /**
     * simpan produk baru (khusus OWNER dan ADMIN)
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required|uuid|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_available' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'workspace_id' => $workspaceId,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'base_price' => $validated['price'],
            'is_active' => $validated['is_available'] ?? true,
        ]);

        return new JsonResponse([
            'message' => 'Produk baru berhasil ditambahkan.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'price' => (float) $product->base_price,
                'is_available' => (bool) $product->is_active,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * perbarui data produk
     */
    public function updateProduct(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $product = Product::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'category_id' => 'sometimes|uuid|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'is_available' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $product->name = $validated['name'];
        }
        if (isset($validated['category_id'])) {
            $product->category_id = $validated['category_id'];
        }
        if (isset($validated['price'])) {
            $product->base_price = $validated['price'];
        }
        if (isset($validated['is_available'])) {
            $product->is_active = $validated['is_available'];
        }
        $product->save();

        return new JsonResponse([
            'message' => 'Produk berhasil diperbarui.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->base_price,
                'is_available' => (bool) $product->is_active,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * hapus produk
     */
    public function deleteProduct(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $product = Product::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $product->delete();

        return new JsonResponse([
            'message' => 'Produk berhasil dihapus.',
        ], Response::HTTP_OK);
    }

    /**
     * simpan kategori baru
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $category = Category::withoutGlobalScopes()->firstOrCreate([
            'workspace_id' => $workspaceId,
            'name' => $validated['name'],
        ]);

        return new JsonResponse([
            'message' => 'Kategori berhasil dibuat.',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => 'MENU',
                'item_count' => 0,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * hapus kategori
     */
    public function deleteCategory(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $category = Category::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $hasProducts = Product::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('category_id', $category->id)
            ->exists();

        if ($hasProducts) {
            return new JsonResponse([
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk aktif.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->delete();

        return new JsonResponse([
            'message' => 'Kategori berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
