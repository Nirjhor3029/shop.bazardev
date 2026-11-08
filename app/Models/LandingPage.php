<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    protected $hidden = ['capi_token'];

    public const TEMPLATES = [
        'classic' => 'Classic: photo, benefits, order form',
        'combo' => 'Combo offer: pick a quantity/price bundle',
        'video' => 'Video-first: demo video at the top',
    ];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'combos' => 'array', 'is_published' => 'boolean', 'capi_token' => 'encrypted'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return $this->store->url().'/lp/'.$this->slug;
    }

    /** Only trusted combo rows (server-stored) are ever used for pricing - never a client-submitted price. */
    public function combo(int $index): ?array
    {
        return $this->combos[$index] ?? null;
    }

    /** YouTube embed URL if video_url is a recognisable YouTube link, so the view never has to parse it. */
    public function youtubeEmbedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|shorts\/|embed\/))([A-Za-z0-9_-]{6,})/', $this->video_url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        return null;
    }
}
