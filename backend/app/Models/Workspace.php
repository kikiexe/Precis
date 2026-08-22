<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'workspaces';

    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'workspace_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class, 'workspace_id');
    }

    public function branchSettings(): HasMany
    {
        return $this->hasMany(BranchSetting::class, 'workspace_id');
    }

    public function posTerminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class, 'workspace_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'workspace_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'workspace_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'workspace_id');
    }

    public function shiftTemplates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class, 'workspace_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class, 'workspace_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'workspace_id');
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class, 'workspace_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'workspace_id');
    }
}
