<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceRolePermission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workspace_role_permissions';

    protected $fillable = [
        'role_id',
        'permission',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(WorkspaceRole::class, 'role_id');
    }
}
