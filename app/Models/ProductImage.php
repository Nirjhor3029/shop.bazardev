<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
