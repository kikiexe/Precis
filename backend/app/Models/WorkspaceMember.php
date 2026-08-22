<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([WorkspaceScope::class])]
class WorkspaceMember extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workspace_members';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'branch_id',
        'role',
        'pin',
        'base_salary',
        'is_active',
    ];

    protected $hidden = [
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'is_active' => 'boolean',
            'pin' => 'hashed',
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

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'workspace_member_id');
    }
}
