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
        'role_id',
        'job_title',
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

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(WorkspaceRole::class, 'role_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'workspace_member_id');
    }

    /**
     * Memeriksa apakah member memiliki izin akses (permission) tertentu.
     * Owner selalu memiliki bypass izin penuh (wildcard).
     *
     * @param string|array<int, string> $permission
     */
    public function hasPermission(string|array $permission): bool
    {
        // 1. Owner selalu memiliki hak akses penuh ke seluruh modul
        if ($this->role === 'OWNER') {
            return true;
        }

        // 2. Jika role kustom terhubung, periksa permission mapping
        $customRole = null;
        if ($this->relationLoaded('customRole')) {
            $customRole = $this->customRole;
        } elseif ($this->role_id) {
            $customRole = WorkspaceRole::withoutGlobalScopes()->with('permissions')->find($this->role_id);
        }

        if ($customRole) {
            $perms = (array) $permission;
            foreach ($perms as $p) {
                if ($customRole->hasPermission($p)) {
                    return true;
                }
            }
        }

        // 3. Fallback compatibility untuk legacy static role (ADMIN / MANAGER / STAFF)
        if ($this->role === 'ADMIN') {
            return true;
        }

        if ($this->role === 'MANAGER') {
            $managerDefaultAllowed = [
                'catalog.view',
                'catalog.manage',
                'inventory.view',
                'inventory.adjust',
                'attendance.view_all',
                'shifts.manage',
                'shifts.approve_swap',
                'sales.view_analytics',
                'cash_advance.approve',
                'members.view',
                'pos.manage_terminals',
                'pos.reprint_receipt',
                'pos.void_order',
                'pos.apply_discount',
            ];
            $perms = (array) $permission;
            foreach ($perms as $p) {
                if (in_array($p, $managerDefaultAllowed, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
