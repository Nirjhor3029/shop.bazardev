<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

/**
 * Storefront: tenant = store matching the `{store}` route segment, reached either as a
 * subdomain ({store}.shop.bazardev.com, registered via Route::domain) or as a path on the
 * main domain (shop.bazardev.com/{store}, the free-plan URL). Draft stores are 404.
 * A store whose plan doesn't allow a subdomain 404s on the subdomain route - the path URL
 * always works regardless of plan, so nothing is ever truly unreachable.
 */
class ResolveStoreFromHost
{
    public function handle(Request $request, Closure $next)
    {
        $slug = strtolower((string) $request->route('store'));
        $store = Store::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $viaSubdomain = $request->getHost() !== config('shop.base_domain');
        abort_if($viaSubdomain && ! $store->activePlan()->allow_subdomain, 404);
        app()->instance('currentStore', $store);
        app()->setLocale(($store->look()['lang'] ?? 'bn') === 'en' ? 'en' : 'bn'); // the store decides its own language
        $request->route()->forgetParameter('store');

        return $next($request);
    }
}
