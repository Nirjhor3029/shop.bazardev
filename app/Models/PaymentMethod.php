<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'config' => 'array', 'secret' => 'encrypted'];
    }
}
