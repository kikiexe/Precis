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
class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'attendances';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'branch_id',
        'shift_assignment_id',
        'clock_in_time',
        'clock_out_time',
        'photo_in_url',
        'photo_out_url',
        'lat_in',
        'lng_in',
        'lat_out',
        'lng_out',
        'status',
        'late_minutes',
        'overtime_minutes',
        'is_manual_override',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'clock_in_time' => 'datetime',
            'clock_out_time' => 'datetime',
            'lat_in' => 'decimal:8',
            'lng_in' => 'decimal:8',
            'lat_out' => 'decimal:8',
            'lng_out' => 'decimal:8',
            'late_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'is_manual_override' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }
}
