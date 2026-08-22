<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([WorkspaceScope::class])]
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'workspace_id',
        'category_id',
        'name',
        'base_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }
}
