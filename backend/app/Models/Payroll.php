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
class Payroll extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payrolls';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'workspace_member_id',
        'period_start',
        'period_end',
        'base_salary',
        'overtime_pay',
        'late_penalty',
        'cash_advance_deduction',
        'net_salary',
        'status',
        'disbursed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'base_salary' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'late_penalty' => 'decimal:2',
            'cash_advance_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'disbursed_at' => 'datetime',
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

    public function workspaceMember(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMember::class, 'workspace_member_id');
    }
}
