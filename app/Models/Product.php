<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'sale_price' => 'decimal:2', 'is_active' => 'boolean', 'is_sample' => 'boolean', 'is_featured' => 'boolean', 'specs' => 'array'];
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function coverUrl(): ?string
    {
        return $this->images->first()?->url();
    }

    public function currentPrice(): string
    {
        return (string) ($this->sale_price ?? $this->price);
    }

    /** The option names used across this product's variants, e.g. ["Color", "Size"], in first-seen order. */
    public function optionNames(): array
    {
        return $this->variants->flatMap(fn ($v) => array_keys($v->options ?? []))->unique()->values()->all();
    }

    /** $variant's own price if it overrides one, otherwise the product's own price. */
    public function priceFor(?ProductVariant $variant = null): string
    {
        if ($variant && $variant->price !== null) {
            return (string) ($variant->sale_price ?? $variant->price);
        }

        return $this->currentPrice();
    }

    public function stockFor(?ProductVariant $variant = null): int
    {
        return $variant ? $variant->stock : $this->stock;
    }
}
