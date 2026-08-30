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

#[ScopedBy([WorkspaceScope::class, BranchScope::class])]
class PosSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'opening_cash',
        'closing_cash_actual',
        'closing_cash_expected',
        'discrepancy_amount',
        'notes',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'closing_cash_actual' => 'decimal:2',
            'closing_cash_expected' => 'decimal:2',
            'discrepancy_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'pos_session_id');
    }

    /**
     * relasi pengeluaran kas belanja outlet pada sesi kasir
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(OutletPurchase::class, 'pos_session_id');
    }
}
