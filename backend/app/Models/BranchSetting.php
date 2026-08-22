<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([WorkspaceScope::class])]
class BranchSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'branch_settings';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'late_penalty_per_minute',
        'overtime_pay_per_hour',
        'min_overtime_threshold_minutes',
    ];

    protected function casts(): array
    {
        return [
            'late_penalty_per_minute' => 'decimal:2',
            'overtime_pay_per_hour' => 'decimal:2',
            'min_overtime_threshold_minutes' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
