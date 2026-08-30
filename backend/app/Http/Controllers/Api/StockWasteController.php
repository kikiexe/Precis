<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Inventory\CreateStockWasteRequest;
use App\Models\Branch;
use App\Models\StockWaste;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockWasteController
{
    /**
     * tampilkan daftar pencatatan barang rusak / terbuang (stock waste)
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $query = StockWaste::query()
            ->with(['product:id,name', 'recordedByUser:id,name', 'branch:id,name'])
            ->where('workspace_id', $workspaceId);

        // isolasi ketat cabang untuk staf dan manajer non-owner
        if (! $isOwner && $member && $member->branch_id) {
            $query->where('branch_id', $member->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->query('reason'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->query('end_date'));
        }

        $wastes = $query->orderByDesc('created_at')->get();

        $totalLossSum = (float) $wastes->sum('total_loss_cost');

        return response()->json([
            'message' => 'Data riwayat stock waste berhasil dimuat.',
            'total_loss_cost' => $totalLossSum,
            'count' => $wastes->count(),
            'data' => $wastes,
        ]);
    }

    /**
     * catat pengeluaran stock waste baru
     */
    public function store(CreateStockWasteRequest $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $branchId = $request->input('branch_id');
        if (! $branchId) {
            $branchId = $member?->branch_id;
        }

        if (! $branchId) {
            $defaultBranch = Branch::where('workspace_id', $workspaceId)->first();
            $branchId = $defaultBranch?->id;
        }

        // cegah admin cabang merekam waste di cabang lain
        if (! $isOwner && $member && $member->branch_id && $branchId !== $member->branch_id) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses untuk mencatat waste pada cabang lain.',
            ], 403);
        }

        $quantity = (float) $request->input('quantity');
        $costPerUnit = (float) $request->input('cost_per_unit');
        $totalLossCost = $request->filled('total_loss_cost')
            ? (float) $request->input('total_loss_cost')
            : round($quantity * $costPerUnit, 2);

        $waste = StockWaste::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'product_id' => $request->input('product_id'),
            'item_name' => $request->input('item_name'),
            'quantity' => $quantity,
            'unit' => $request->input('unit', 'Pcs'),
            'cost_per_unit' => $costPerUnit,
            'total_loss_cost' => $totalLossCost,
            'reason' => $request->input('reason'),
            'photo_url' => $request->input('photo_url'),
            'notes' => $request->input('notes'),
            'recorded_by_user_id' => $user->id,
        ]);

        $waste->load(['product:id,name', 'recordedByUser:id,name', 'branch:id,name']);

        return response()->json([
            'message' => 'Pencatatan stock waste berhasil disimpan.',
            'data' => $waste,
        ], 201);
    }

    /**
     * tampilkan detail stock waste
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $waste = StockWaste::withoutGlobalScopes()
            ->with(['product:id,name', 'recordedByUser:id,name', 'branch:id,name'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (! $isOwner && $member && $member->branch_id && $waste->branch_id !== $member->branch_id) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses untuk melihat data waste cabang lain.',
            ], 403);
        }

        return response()->json([
            'data' => $waste,
        ]);
    }

    /**
     * hapus catatan stock waste
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $waste = StockWaste::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (! $isOwner && $member && $member->branch_id && $waste->branch_id !== $member->branch_id) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses untuk menghapus data waste cabang lain.',
            ], 403);
        }

        $waste->delete();

        return response()->json([
            'message' => 'Catatan stock waste berhasil dihapus.',
        ]);
    }
}
