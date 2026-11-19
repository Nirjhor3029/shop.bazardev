<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'last_checked_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /** DNS TXT record name the seller must create, e.g. "_bazardev-verify.mydress.com". */
    public function txtRecordName(): string
    {
        return '_bazardev-verify.'.$this->domain;
    }
}
