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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([WorkspaceScope::class, BranchScope::class])]
class ShiftTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'shift_templates';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'name',
        'expected_clock_in',
        'expected_clock_out',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class, 'shift_template_id');
    }
}
