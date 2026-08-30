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
class StockWaste extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_wastes';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'product_id',
        'item_name',
        'quantity',
        'unit',
        'cost_per_unit',
        'total_loss_cost',
        'reason',
        'photo_url',
        'notes',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'total_loss_cost' => 'decimal:2',
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
     * relasi opsional ke menu produk katalog
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * relasi ke user/staf yang mencatat
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
