<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];
}
