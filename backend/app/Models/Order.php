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
class Order extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orders';

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'pos_session_id',
        'pos_terminal_id',
        'cashier_user_id',
        'client_order_id',
        'order_number',
        'total_amount',
        'discount_amount',
        'final_amount',
        'tax_name',
        'tax_rate',
        'tax_type',
        'tax_amount',
        'payment_method',
        'payment_status',
        'void_reason',
        'voided_at',
        'voided_by_user_id',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'refunded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
