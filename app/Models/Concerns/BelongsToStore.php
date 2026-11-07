<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);
        static::creating(function ($model) {
            if (! $model->store_id && app()->bound('currentStore') && app('currentStore')) {
                $model->store_id = app('currentStore')->id;
            }
            abort_unless($model->store_id, 500, 'store_id missing');
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
