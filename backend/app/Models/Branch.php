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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([WorkspaceScope::class])]
class Branch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'branches';

    protected $fillable = [
        'workspace_id',
        'name',
        'lat',
        'lng',
        'radius_meters',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'radius_meters' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class, 'branch_id');
    }

    public function setting(): HasOne
    {
        return $this->hasOne(BranchSetting::class, 'branch_id');
    }

    public function settings(): HasOne
    {
        return $this->setting();
    }

    public function posTerminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class, 'branch_id');
    }

    public function terminals(): HasMany
    {
        return $this->posTerminals();
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'branch_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'branch_id');
    }

    public function shiftTemplates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class, 'branch_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'branch_id');
    }
}
