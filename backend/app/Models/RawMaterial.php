<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'category_id',
        'current_stock',
        'min_stock_alert',
        'unit',
        'last_adjusted_at',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'min_stock_alert' => 'float',
        'last_adjusted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new WorkspaceScope);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'material_id');
    }
}
