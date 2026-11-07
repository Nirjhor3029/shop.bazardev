<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

/** Storefront (subdomain): tenant = store matching the host. Draft stores are 404. */
class ResolveStoreFromHost
{
    public function handle(Request $request, Closure $next)
    {
        $slug = strtolower((string) $request->route('store'));
        $store = Store::where('slug', $slug)->where('status', 'published')->firstOrFail();
        app()->instance('currentStore', $store);
        app()->setLocale(($store->look()['lang'] ?? 'bn') === 'en' ? 'en' : 'bn'); // the store decides its own language
        $request->route()->forgetParameter('store');

        return $next($request);
    }
}
