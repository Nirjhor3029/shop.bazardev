<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    protected $fillable = ['user_id', 'slug', 'name', 'status', 'theme', 'settings', 'plan_id', 'plan_expires_at'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'plan_expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** The plan in effect right now - falls back to the default plan if unset or expired, so a lapsed subscription just degrades to Free rather than breaking. */
    public function activePlan(): Plan
    {
        if ($this->plan && (! $this->plan_expires_at || $this->plan_expires_at->isFuture())) {
            return $this->plan;
        }

        return Plan::default();
    }

    public const THEMES = [
        'minimal' => 'Minimal: clean white, rounded cards',
        'bold' => 'Bold: dark header, strong colors',
        'boutique' => 'Boutique: soft, serif headings',
        'vibrant' => 'Vibrant: playful, rounded, colorful',
        'mono' => 'Mono: editorial black & white',
    ];

    public const FONTS = ['sans' => 'Modern (sans-serif)', 'serif' => 'Classic (serif)'];

    public const OVERLAYS = [
        'dark' => 'Dark (default, best text contrast)',
        'soft' => 'Soft (lighter dimming)',
        'none' => 'None (use a pre-darkened banner)',
    ];

    public const SECTIONS = [
        'hero' => 'Banner / hero',
        'categories' => 'Category buttons',
        'featured' => 'Featured products (horizontal strip)',
        'products' => 'Product grid',
        'trust' => 'Trust badges (COD/bKash/Nagad icons)',
        'about' => 'About and contact',
    ];

    /** Look and content settings with safe defaults. New section types added later are backfilled automatically, so existing stores don't need to resave appearance to pick them up. */
    public function look(): array
    {
        $lang = $this->settings['look']['lang'] ?? 'bn';
        $defaults = [
            'font' => 'sans', 'announcement' => '', 'hero_title' => '', 'hero_text' => '',
            'hero_button' => $lang === 'bn' ? 'এখনই কিনুন' : 'Shop now',
            'about' => '', 'phone' => '', 'email' => '', 'address' => '', 'facebook' => '',
            'logo' => null, 'banner' => null, 'whatsapp' => '', 'digits' => true, 'lang' => 'bn', 'overlay' => 'dark',
        ];

        $merged = array_merge($defaults, $this->settings['look'] ?? []);

        // Keep the seller's saved order; just append any section type that's new since they last saved.
        $saved = $merged['sections'] ?? [];
        $savedTypes = collect($saved)->pluck('type')->all();
        $missing = collect(self::SECTIONS)->keys()->reject(fn ($t) => in_array($t, $savedTypes, true))
            ->map(fn ($t) => ['type' => $t, 'on' => true]);
        $merged['sections'] = array_values([...$saved, ...$missing]);

        return $merged;
    }

    public function sections(): array
    {
        return collect($this->look()['sections'])->filter(fn ($s) => isset(self::SECTIONS[$s['type']]) && $s['on'])->pluck('type')->all();
    }

    public function fileUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** Paid (subdomain-allowed) stores get the branded subdomain; everyone else gets a path on the main domain. */
    public function url(): string
    {
        return $this->activePlan()->allow_subdomain
            ? 'https://'.$this->slug.'.'.config('shop.base_domain')
            : 'https://'.config('shop.base_domain').'/'.$this->slug;
    }

    /**
     * Internal storefront links/redirects must go through this - a path-based (non-subdomain)
     * store lives under /{slug}/..., so a plain root-relative "/cart" would 404 for it.
     * $path is a clean path ("/", "/cart", "/p/x"); append any query string after calling this.
     */
    public function storePath(string $path = '/'): string
    {
        $base = $this->activePlan()->allow_subdomain ? '' : '/'.$this->slug;

        return $path === '/' ? ($base ?: '/') : $base.$path;
    }
}
