<?php

namespace App\Services;

use App\Models\StoreDomain;
use Illuminate\Support\Str;

/**
 * Ownership proof for a seller-claimed custom domain: they must publish a TXT record
 * containing our random token at _bazardev-verify.<their-domain>. DNS lookup failures
 * (not-yet-propagated, no record, resolver error) are all treated as "not verified yet" -
 * never as an error that blocks the seller from retrying.
 */
class DomainVerifier
{
    public function newToken(): string
    {
        return Str::random(32);
    }

    public function check(StoreDomain $domain): bool
    {
        $domain->forceFill(['last_checked_at' => now()]);

        if ($this->txtRecordMatches($domain->domain, $domain->verification_token)) {
            $domain->forceFill(['status' => 'verified', 'verified_at' => now()])->save();

            return true;
        }

        $domain->forceFill(['status' => 'failed'])->save();

        return false;
    }

    private function txtRecordMatches(string $domain, string $token): bool
    {
        $name = '_bazardev-verify.'.$domain;

        try {
            $records = @dns_get_record($name, DNS_TXT);
        } catch (\Throwable $e) {
            return false;
        }

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            if (($record['txt'] ?? null) === $token) {
                return true;
            }
        }

        return false;
    }
}
