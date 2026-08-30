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
class WorkspaceRole extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workspace_roles';

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(WorkspaceRolePermission::class, 'role_id')->orderBy('permission', 'asc');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class, 'role_id');
    }

    /**
     * cek apakah role memiliki permission tertentu
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('permission', $permission);
    }

    /**
     * dapatkan array slug permission
     *
     * @return array<int, string>
     */
    public function getPermissionSlugs(): array
    {
        return $this->permissions->pluck('permission')->toArray();
    }
}
