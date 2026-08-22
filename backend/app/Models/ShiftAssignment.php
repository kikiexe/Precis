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

#[ScopedBy([WorkspaceScope::class])]
class ShiftAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'shift_assignments';

    protected $fillable = [
        'workspace_id',
        'shift_template_id',
        'assigned_user_id',
        'actual_user_id',
        'date',
        'is_swap',
        'swap_status',
        'swap_approved_by_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_swap' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function actualUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actual_user_id');
    }

    public function swapApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swap_approved_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'shift_assignment_id');
    }
}
