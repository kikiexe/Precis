<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class SuperadminInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'amount_base' => (float) $this->amount_base,
            'unique_code' => $this->unique_code,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'due_date' => $this->due_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'subscription_status' => $this->user->subscription_status,
                'workspaces' => $this->user->ownedWorkspaces->map(fn (Workspace $ws): array => [
                    'id' => $ws->id,
                    'name' => $ws->name,
                    'slug' => $ws->slug,
                ])->toArray(),
            ] : null,
            'confirmation' => $this->confirmation ? [
                'id' => $this->confirmation->id,
                'bank_account_name' => $this->confirmation->bank_account_name,
                'transfer_amount' => (float) $this->confirmation->transfer_amount,
                'proof_image_url' => $this->confirmation->proof_image_url,
                'verified_at' => $this->confirmation->verified_at?->toIso8601String(),
                'created_at' => $this->confirmation->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
