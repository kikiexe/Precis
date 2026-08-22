<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $request = request();
        if ($request && $request->attributes->has('current_member')) {
            $member = $request->attributes->get('current_member');
            // If the member is tied to a specific branch (Manager / Staff), restrict queries to that branch
            if ($member && ! empty($member->branch_id)) {
                $builder->where($model->getTable() . '.branch_id', $member->branch_id);
            }
        }
    }
}
