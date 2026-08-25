<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Branch;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class TenantDirectoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = Carbon::now();
        $expiresAt = $this->subscription_expires_at ? Carbon::parse($this->subscription_expires_at) : null;
        $daysRemaining = $expiresAt ? (int) $now->diffInDays($expiresAt, false) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'subscription_status' => $this->subscription_status,
            'subscription_expires_at' => $this->subscription_expires_at?->toIso8601String(),
            'days_remaining' => $daysRemaining,
            'max_workspaces' => $this->max_workspaces,
            'created_at' => $this->created_at?->toIso8601String(),
            'workspaces' => $this->ownedWorkspaces->map(fn (Workspace $ws): array => [
                'id' => $ws->id,
                'name' => $ws->name,
                'slug' => $ws->slug,
                'status' => $ws->status,
                'branches_count' => $ws->branches->count(),
                'branches' => $ws->branches->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ])->toArray(),
            ])->toArray(),
        ];
    }
}
