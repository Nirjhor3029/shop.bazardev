<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    public const FLOW = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function nextStatuses(): array
    {
        return self::FLOW[$this->status] ?? [];
    }
}
