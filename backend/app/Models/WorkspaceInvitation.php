<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workspace_invitations';

    protected $fillable = [
        'workspace_id',
        'invited_by_user_id',
        'email',
        'job_title',
        'role',
        'base_salary',
        'branch_id',
        'token',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
