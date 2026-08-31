<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'material_id',
        'reason',
        'adjusted_amount',
        'resulting_stock',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'adjusted_amount' => 'float',
        'resulting_stock' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new WorkspaceScope);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'material_id');
    }
}
