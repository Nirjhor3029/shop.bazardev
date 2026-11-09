<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    protected function casts(): array
    {
        return ['options' => 'array', 'price' => 'decimal:2', 'sale_price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class);
    }

    /** "Color: Red, Size: M" - a human label, snapshotted onto order items since variants can change later. */
    public function label(): string
    {
        return collect($this->options)->map(fn ($v, $k) => "$k: $v")->implode(', ');
    }
}
