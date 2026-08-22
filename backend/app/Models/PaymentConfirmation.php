<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConfirmation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_confirmations';

    protected $fillable = [
        'invoice_id',
        'user_id',
        'bank_account_name',
        'transfer_amount',
        'proof_image_url',
        'verified_by_superadmin_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifiedBySuperadmin(): BelongsTo
    {
        return $this->belongsTo(Superadmin::class, 'verified_by_superadmin_id');
    }
}
