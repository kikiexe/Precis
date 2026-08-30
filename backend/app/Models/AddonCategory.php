<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([WorkspaceScope::class])]
class AddonCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'addon_categories';

    protected $fillable = [
        'workspace_id',
        'name',
        'selection_type',
        'is_required',
        'min_selection',
        'max_selection',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'min_selection' => 'integer',
            'max_selection' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(Addon::class, 'addon_category_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_addon_categories', 'addon_category_id', 'product_id');
    }
}
