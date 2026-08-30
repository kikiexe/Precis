<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pos\CreateOutletPurchaseRequest;
use App\Models\Branch;
use App\Models\OutletPurchase;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OutletPurchaseController
{
    /**
     * ambil daftar belanja operasional outlet (petty cash)
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $query = OutletPurchase::query()
            ->with(['branch:id,name', 'recordedByUser:id,name', 'session:id,opened_at'])
            ->where('workspace_id', $workspaceId);

        // isolasi cabang untuk non-owner
        if (! $isOwner && $member && $member->branch_id) {
            $query->where('branch_id', $member->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('funding_source')) {
            $query->where('funding_source', $request->query('funding_source'));
        }

        if ($request->filled('pos_session_id')) {
            $query->where('pos_session_id', $request->query('pos_session_id'));
        }

        $purchases = $query->orderByDesc('created_at')->get();

        return new JsonResponse([
            'message' => 'Daftar belanja outlet berhasil dimuat.',
            'total_amount' => (float) $purchases->sum('total_price'),
            'count' => $purchases->count(),
            'data' => $purchases,
        ], Response::HTTP_OK);
    }

    /**
     * simpan data pengeluaran belanja operasional outlet dari portal web
     */
    public function store(CreateOutletPurchaseRequest $request): JsonResponse
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

        if (! $isOwner && $member && $member->branch_id && $branchId !== $member->branch_id) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki hak akses untuk mencatat pengeluaran di cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $quantity = (float) $request->input('quantity');
        $unitPrice = (float) $request->input('unit_price');
        $totalPrice = $request->filled('total_price')
            ? (float) $request->input('total_price')
            : round($quantity * $unitPrice, 2);

        $purchase = OutletPurchase::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branchId,
            'pos_session_id' => $request->input('pos_session_id'),
            'item_name' => $request->input('item_name'),
            'unit' => $request->input('unit', 'Pcs'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'category' => $request->input('category'),
            'funding_source' => $request->input('funding_source'),
            'receipt_photo_url' => $request->input('receipt_photo_url'),
            'notes' => $request->input('notes'),
            'recorded_by_user_id' => $user->id,
        ]);

        $purchase->load(['branch:id,name', 'recordedByUser:id,name']);

        return new JsonResponse([
            'message' => 'Belanja outlet berhasil dicatat.',
            'data' => $purchase,
        ], Response::HTTP_CREATED);
    }

    /**
     * ambil detail satu transaksi belanja outlet
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $purchase = OutletPurchase::withoutGlobalScopes()
            ->with(['branch:id,name', 'recordedByUser:id,name', 'session'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (! $isOwner && $member && $member->branch_id && $purchase->branch_id !== $member->branch_id) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki hak akses untuk melihat data belanja cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'data' => $purchase,
        ], Response::HTTP_OK);
    }

    /**
     * hapus catatan belanja outlet
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $user = $request->user();

        $member = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $user->is_superadmin || ($member && $member->role === 'OWNER');

        $purchase = OutletPurchase::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (! $isOwner && $member && $member->branch_id && $purchase->branch_id !== $member->branch_id) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki hak akses untuk menghapus data belanja cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $purchase->delete();

        return new JsonResponse([
            'message' => 'Catatan belanja outlet berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
