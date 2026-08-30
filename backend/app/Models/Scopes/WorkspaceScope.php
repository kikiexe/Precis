<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WorkspaceScope implements Scope
{
    /**
     * terapkan pembatasan workspace pada query builder eloquent
     */
    public function apply(Builder $builder, Model $model): void
    {
        $request = request();
        if ($request && $request->attributes->has('current_workspace_id')) {
            $workspaceId = $request->attributes->get('current_workspace_id');
            if ($workspaceId) {
                $builder->where($model->getTable() . '.workspace_id', $workspaceId);
            }
        }
    }
}
