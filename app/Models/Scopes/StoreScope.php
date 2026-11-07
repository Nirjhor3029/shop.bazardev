<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Every tenant query is limited to the current store. No current store = no rows (fail closed). */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $store = app()->bound('currentStore') ? app('currentStore') : null;
        $store
            ? $builder->where($model->getTable().'.store_id', $store->id)
            : $builder->whereRaw('0 = 1');
    }
}
