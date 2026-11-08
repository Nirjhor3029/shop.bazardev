<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Everything a seller can/can't do is driven by their Plan - prices, limits and feature toggles are all super-admin configured, never hardcoded. */
class Plan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'landing_templates' => 'array', 'storefront_themes' => 'array',
            'allow_subdomain' => 'boolean', 'allow_sslcommerz' => 'boolean', 'allow_pixel_tracking' => 'boolean',
            'remove_branding' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    public static function default(): self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('id')->firstOrFail();
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /** Null list = every template/theme allowed, including ones added after this plan was created. */
    public function allowsTemplate(string $key): bool
    {
        return $this->landing_templates === null || in_array($key, $this->landing_templates, true);
    }

    public function allowsTheme(string $key): bool
    {
        return $this->storefront_themes === null || in_array($key, $this->storefront_themes, true);
    }
}
