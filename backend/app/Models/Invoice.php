<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'invoices';

    protected $fillable = [
        'user_id',
        'invoice_number',
        'amount_base',
        'unique_code',
        'total_amount',
        'payment_gateway_ref',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_base' => 'decimal:2',
            'unique_code' => 'integer',
            'total_amount' => 'decimal:2',
            'due_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmation(): HasOne
    {
        return $this->hasOne(PaymentConfirmation::class, 'invoice_id');
    }
}
