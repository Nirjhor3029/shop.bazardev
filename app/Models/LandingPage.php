<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use BelongsToStore;

    protected $guarded = ['id', 'store_id'];

    public const TEMPLATES = [
        'classic' => 'Classic: photo, benefits, order form',
    ];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'is_published' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return $this->store->url().'/lp/'.$this->slug;
    }
}
