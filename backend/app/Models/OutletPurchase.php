<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\BranchScope;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([WorkspaceScope::class, BranchScope::class])]
class OutletPurchase extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'outlet_purchases';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'pos_session_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'category',
        'funding_source',
        'receipt_photo_url',
        'notes',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * relasi ke workspace tenant
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    /**
     * relasi ke outlet cabang
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * relasi ke sesi kasir terkait
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    /**
     * relasi ke kasir / staf pencatat
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
