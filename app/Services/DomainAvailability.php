<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Free, keyless domain-availability lookup via RDAP (the standardised successor to WHOIS).
 * The per-TLD server address comes from IANA's public bootstrap registry, cached a day at a
 * time, so we never hardcode (and can't go stale on) which registry serves which TLD.
 */
class DomainAvailability
{
    /** Shown as "also try these extensions" alongside whatever the visitor actually typed. All RDAP-checkable for free. */
    public const COMMON_TLDS = ['com', 'net', 'org', 'shop', 'store', 'online', 'site', 'xyz', 'info', 'app', 'biz'];

    /** @return array{domain: string, status: string, available: ?bool} status: available|taken|unknown|unsupported|invalid */
    public function check(string $raw): array
    {
        $domain = $this->normalizeDomain($raw);
        if (! $domain) {
            return ['domain' => $raw, 'status' => 'invalid', 'available' => null];
        }

        try {
            return Cache::remember("domain-check:{$domain}", now()->addMinutes(5), fn () => $this->lookupOne($domain));
        } catch (\Throwable) {
            return ['domain' => $domain, 'status' => 'unknown', 'available' => null];
        }
    }

    /**
     * Checks the same base name across COMMON_TLDS (e.g. "nirjhor" -> nirjhor.com, nirjhor.shop, ...),
     * fired concurrently so showing 11 extensions costs one round trip, not eleven sequential ones.
     *
     * @return array<int, array{domain: string, tld: string, status: string, available: ?bool}>
     */
    public function checkAcrossCommonTlds(string $raw): array
    {
        $label = $this->extractLabel($raw);
        if (! $label) {
            return [];
        }

        $candidates = collect(self::COMMON_TLDS)->mapWithKeys(fn ($tld) => ["{$label}.{$tld}" => $tld]);

        $results = [];
        $pending = []; // domain => rdap base url, only for cache-misses we can actually resolve
        foreach ($candidates as $domain => $tld) {
            if ($cached = Cache::get("domain-check:{$domain}")) {
                $results[$domain] = $cached;

                continue;
            }
            if ($base = $this->rdapBaseFor($tld)) {
                $pending[$domain] = $base;
            } else {
                $results[$domain] = ['domain' => $domain, 'status' => 'unsupported', 'available' => null];
            }
        }

        if ($pending) {
            try {
                $responses = Http::pool(fn ($pool) => collect($pending)->map(
                    fn ($base, $domain) => $pool->as($domain)->timeout(5)->get(rtrim($base, '/').'/domain/'.rawurlencode($domain))
                )->all());
            } catch (\Throwable) {
                $responses = [];
            }
            foreach ($pending as $domain => $base) {
                $result = ['domain' => $domain, ...$this->interpret($responses[$domain] ?? null)];
                Cache::put("domain-check:{$domain}", $result, now()->addMinutes(5));
                $results[$domain] = $result;
            }
        }

        return $candidates->map(fn ($tld, $domain) => [...$results[$domain], 'tld' => $tld])->values()->all();
    }

    private function lookupOne(string $domain): array
    {
        $tld = substr($domain, strrpos($domain, '.') + 1);
        $base = $this->rdapBaseFor($tld);
        if (! $base) {
            return ['domain' => $domain, 'status' => 'unsupported', 'available' => null];
        }

        try {
            $res = Http::timeout(5)->get(rtrim($base, '/').'/domain/'.rawurlencode($domain));
        } catch (RequestException $e) {
            if (! $e->response) {
                throw $e;
            }
            $res = $e->response; // see interpret() - some RDAP servers trip Laravel's client this way
        }

        return ['domain' => $domain, ...$this->interpret($res)];
    }

    /**
     * $res is normally a Response, but pool entries land here as whatever Http::pool() gave back for
     * a failed slot, and some RDAP servers (e.g. Verisign) send an empty-bodied 404 over HTTP/2 that
     * Laravel's client surfaces as a RequestException even though a real, usable response came back.
     *
     * @return array{status: string, available: ?bool}
     */
    private function interpret(mixed $res): array
    {
        if ($res instanceof RequestException && $res->response) {
            $res = $res->response;
        }
        if (! $res instanceof Response) {
            return ['status' => 'unknown', 'available' => null];
        }
        if ($res->status() === 404) {
            return ['status' => 'available', 'available' => true];
        }
        if ($res->successful()) {
            return ['status' => 'taken', 'available' => false];
        }

        return ['status' => 'unknown', 'available' => null];
    }

    /** @return array<string, string> TLD (no dot) => RDAP base URL */
    private function bootstrapMap(): array
    {
        return Cache::remember('rdap-bootstrap-map', now()->addDay(), function () {
            $services = Http::timeout(5)->get('https://data.iana.org/rdap/dns.json')->json('services', []);
            $out = [];
            foreach ($services as $service) {
                [$tlds, $urls] = $service;
                foreach ($tlds as $tld) {
                    $out[$tld] = $urls[0] ?? null;
                }
            }

            return $out;
        });
    }

    private function rdapBaseFor(string $tld): ?string
    {
        return $this->bootstrapMap()[$tld] ?? null;
    }

    /** Strips scheme/www/path, lowercases, and rejects anything that isn't a plausible full domain. */
    private function normalizeDomain(string $raw): ?string
    {
        $d = $this->stripToHost($raw);

        if (! preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*\.[a-z]{2,24}$/', $d)) {
            return null;
        }

        return $d;
    }

    /** Pulls out just the brand/name part ("nirjhor" from "nirjhor.com", "https://nirjhor", "nirjhor" ...). */
    private function extractLabel(string $raw): ?string
    {
        $label = explode('.', $this->stripToHost($raw))[0];

        return preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', $label) ? $label : null;
    }

    private function stripToHost(string $raw): string
    {
        $d = strtolower(trim($raw));
        $d = preg_replace('#^https?://#', '', $d);
        $d = preg_replace('#^www\.#', '', $d);
        $d = explode('/', $d)[0];
        $d = explode('?', $d)[0];

        return rtrim($d, '.');
    }
}
