<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'max_workspaces',
        'monthly_price',
        'annual_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_workspaces' => 'integer',
            'monthly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'plan_id');
    }
}
