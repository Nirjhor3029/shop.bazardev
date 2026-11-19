<?php

namespace App\Http\Middleware;

use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;

/**
 * Storefront: tenant = the store that owns this verified custom domain (e.g. mydress.com).
 * Caddy's catch-all site block only forwards a request here once its own on-demand-TLS ask
 * endpoint has already confirmed a matching verified StoreDomain exists; this middleware
 * re-checks the same thing at the Laravel layer, and re-checks the plan live too - a seller
 * who loses Pro loses the domain immediately, not just at next verification.
 */
class ResolveStoreFromCustomDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());
        $domain = StoreDomain::where('domain', $host)->where('status', 'verified')->first();
        abort_if(! $domain, 404);

        $store = $domain->store;
        abort_if(! $store || $store->status !== 'published', 404);
        abort_unless($store->activePlan()->allow_custom_domain, 404);

        app()->instance('currentStore', $store);
        app()->setLocale(($store->look()['lang'] ?? 'bn') === 'en' ? 'en' : 'bn');
        $request->route()->forgetParameter('customDomain');

        return $next($request);
    }
}
