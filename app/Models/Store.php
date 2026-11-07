<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $fillable = ['user_id', 'slug', 'name', 'status', 'theme', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const THEMES = [
        'minimal' => 'Minimal: clean white, rounded cards',
        'bold' => 'Bold: dark header, strong colors',
        'boutique' => 'Boutique: soft, serif headings',
    ];

    public const FONTS = ['sans' => 'Modern (sans-serif)', 'serif' => 'Classic (serif)'];

    public const SECTIONS = [
        'hero' => 'Banner / hero',
        'categories' => 'Category buttons',
        'products' => 'Product grid',
        'about' => 'About and contact',
    ];

    /** Look and content settings with safe defaults. */
    public function look(): array
    {
        $defaults = [
            'font' => 'sans', 'announcement' => '', 'hero_title' => '', 'hero_text' => '', 'hero_button' => 'Shop now',
            'about' => '', 'phone' => '', 'email' => '', 'address' => '', 'facebook' => '',
            'logo' => null, 'banner' => null,
            'sections' => collect(self::SECTIONS)->keys()->map(fn ($t) => ['type' => $t, 'on' => true])->all(),
        ];

        return array_merge($defaults, $this->settings['look'] ?? []);
    }

    public function sections(): array
    {
        $saved = collect($this->look()['sections'])->pluck('on', 'type');

        return collect($this->look()['sections'])->filter(fn ($s) => isset(self::SECTIONS[$s['type']]) && $s['on'])->pluck('type')->all();
    }

    public function fileUrl(?string $path): ?string
    {
        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    }

    public function url(): string
    {
        return 'https://'.$this->slug.'.'.config('shop.base_domain');
    }
}
