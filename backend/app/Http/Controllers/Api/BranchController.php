<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\PosTerminal;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BranchController
{
    /**
     * ambil daftar seluruh cabang dalam workspace aktif beserta pengaturan dan terminal
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $branches = Branch::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['setting', 'posTerminals'])
            ->get()
            ->map(function (Branch $b): array {
                $setting = $b->setting;

                return [
                    'id' => $b->id,
                    'workspace_id' => $b->workspace_id,
                    'name' => $b->name,
                    'lat' => (float) $b->lat,
                    'lng' => (float) $b->lng,
                    'radius_meters' => (int) $b->radius_meters,
                    'qris_image_url' => $b->qris_image_url,
                    'late_penalty_per_minute' => $setting ? (float) $setting->late_penalty_per_minute : 1000.0,
                    'overtime_pay_per_hour' => $setting ? (float) $setting->overtime_pay_per_hour : 20000.0,
                    'min_overtime_threshold_minutes' => $setting ? (int) $setting->min_overtime_threshold_minutes : 30,
                    'tax_enabled' => $setting ? (bool) $setting->tax_enabled : false,
                    'tax_name' => $setting ? (string) $setting->tax_name : 'PB1',
                    'tax_rate' => $setting ? (float) $setting->tax_rate : 10.0,
                    'tax_type' => $setting ? (string) $setting->tax_type : 'INCLUSIVE',
                    'show_tax_on_receipt' => $setting ? (bool) $setting->show_tax_on_receipt : true,
                    'terminals_count' => $b->posTerminals->count(),
                    'terminals' => $b->posTerminals->map(function (PosTerminal $t): array {
                        return [
                            'id' => $t->id,
                            'terminal_name' => $t->terminal_name,
                            'device_token_preview' => 'pos_tok_' . substr($t->device_token_hash, 0, 6) . '***',
                            'is_paired' => ! empty($t->device_token_hash),
                            'is_active' => (bool) $t->is_active,
                            'created_at' => $t->created_at?->toIso8601String(),
                        ];
                    })->values(),
                    'created_at' => $b->created_at?->toIso8601String(),
                ];
            });

        return new JsonResponse([
            'message' => 'Daftar cabang berhasil dimuat.',
            'data' => $branches,
        ], Response::HTTP_OK);
    }

    /**
     * perbarui pengaturan cabang dan geofence (khusus OWNER dan ADMIN)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if ($member && $member->role !== 'OWNER' && $member->branch_id !== null && $member->branch_id !== $id) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Anda tidak berwenang mengelola pengaturan pada cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
            'radius_meters' => 'sometimes|integer|min:10|max:1000',
            'qris_image_url' => 'nullable|string|max:500',
            'late_penalty_per_minute' => 'sometimes|numeric|min:0',
            'overtime_pay_per_hour' => 'sometimes|numeric|min:0',
            'min_overtime_threshold_minutes' => 'sometimes|integer|min:0|max:180',
            'tax_enabled' => 'sometimes|boolean',
            'tax_name' => 'sometimes|string|max:100',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'tax_type' => 'sometimes|string|in:INCLUSIVE,EXCLUSIVE',
            'show_tax_on_receipt' => 'sometimes|boolean',
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
        if (array_key_exists('qris_image_url', $validated)) {
            $branch->qris_image_url = $validated['qris_image_url'];
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
                'tax_enabled' => false,
                'tax_name' => 'PB1',
                'tax_rate' => 10.00,
                'tax_type' => 'INCLUSIVE',
                'show_tax_on_receipt' => true,
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
        if (isset($validated['tax_enabled'])) {
            $setting->tax_enabled = (bool) $validated['tax_enabled'];
        }
        if (isset($validated['tax_name'])) {
            $setting->tax_name = $validated['tax_name'];
        }
        if (isset($validated['tax_rate'])) {
            $setting->tax_rate = $validated['tax_rate'];
        }
        if (isset($validated['tax_type'])) {
            $setting->tax_type = $validated['tax_type'];
        }
        if (isset($validated['show_tax_on_receipt'])) {
            $setting->show_tax_on_receipt = (bool) $validated['show_tax_on_receipt'];
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
                'qris_image_url' => $branch->qris_image_url,
                'late_penalty_per_minute' => (float) $setting->late_penalty_per_minute,
                'overtime_pay_per_hour' => (float) $setting->overtime_pay_per_hour,
                'min_overtime_threshold_minutes' => (int) $setting->min_overtime_threshold_minutes,
                'tax_enabled' => (bool) $setting->tax_enabled,
                'tax_name' => (string) $setting->tax_name,
                'tax_rate' => (float) $setting->tax_rate,
                'tax_type' => (string) $setting->tax_type,
                'show_tax_on_receipt' => (bool) $setting->show_tax_on_receipt,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * buat terminal POS baru untuk cabang dengan token pairing baru
     */
    public function createTerminal(Request $request, string $branchId): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if ($member && $member->role !== 'OWNER' && $member->branch_id !== null && $member->branch_id !== $branchId) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Anda tidak berwenang mengelola terminal pada cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $branch = Branch::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $branchId)
            ->firstOrFail();

        $validated = $request->validate([
            'terminal_name' => 'nullable|string|max:100',
            'device_token' => 'nullable|string|min:3|max:100',
        ]);

        $name = ! empty($validated['terminal_name'])
            ? trim($validated['terminal_name'])
            : 'Terminal Kasir #' . (PosTerminal::withoutGlobalScopes()->where('branch_id', $branch->id)->count() + 1);

        $rawToken = ! empty($validated['device_token'])
            ? trim($validated['device_token'])
            : 'pos-' . Str::slug($branch->name) . '-' . Str::lower(Str::random(6));

        $terminal = PosTerminal::create([
            'workspace_id' => $workspaceId,
            'branch_id' => $branch->id,
            'terminal_name' => $name,
            'device_token_hash' => hash('sha256', $rawToken),
            'is_active' => true,
        ]);

        return new JsonResponse([
            'message' => 'Terminal kasir baru berhasil dibuat.',
            'data' => [
                'id' => $terminal->id,
                'terminal_name' => $terminal->terminal_name,
                'device_token' => $rawToken,
                'is_active' => $terminal->is_active,
                'created_at' => $terminal->created_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * regenerasi atau setel token perangkat kustom untuk terminal POS
     */
    public function regenerateTerminalToken(Request $request, string $branchId, string $terminalId): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if ($member && $member->role !== 'OWNER' && $member->branch_id !== null && $member->branch_id !== $branchId) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Anda tidak berwenang mengelola terminal pada cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'device_token' => 'nullable|string|min:3|max:100',
        ]);

        $terminal = PosTerminal::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('id', $terminalId)
            ->firstOrFail();

        $branch = Branch::withoutGlobalScopes()->find($branchId);
        $branchSlug = $branch ? Str::slug($branch->name) : 'outlet';

        $rawToken = ! empty($validated['device_token'])
            ? trim($validated['device_token'])
            : 'pos-' . $branchSlug . '-' . Str::lower(Str::random(6));

        $terminal->update([
            'device_token_hash' => hash('sha256', $rawToken),
        ]);

        return new JsonResponse([
            'message' => 'Token terminal kasir berhasil diperbarui.',
            'data' => [
                'id' => $terminal->id,
                'terminal_name' => $terminal->terminal_name,
                'device_token' => $rawToken,
                'is_active' => $terminal->is_active,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * hapus terminal POS
     */
    public function deleteTerminal(Request $request, string $branchId, string $terminalId): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if ($member && $member->role !== 'OWNER' && $member->branch_id !== null && $member->branch_id !== $branchId) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Anda tidak berwenang mengelola terminal pada cabang lain.',
            ], Response::HTTP_FORBIDDEN);
        }

        $terminal = PosTerminal::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('branch_id', $branchId)
            ->where('id', $terminalId)
            ->firstOrFail();

        $terminal->delete();

        return new JsonResponse([
            'message' => 'Terminal kasir berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
