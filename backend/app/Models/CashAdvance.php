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
class CashAdvance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cash_advances';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'amount',
        'request_date',
        'status',
        'approved_by_user_id',
        'deducted_at_payroll_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_date' => 'date',
            'deducted_at_payroll_date' => 'date',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
