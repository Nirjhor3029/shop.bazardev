<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Marketplace + auth pages: Bangla by default, English if the visitor chose it (cookie `lang`). */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // A storefront page (subdomain or path URL) uses the store's own language, set by
        // ResolveStoreFromHost - which runs before this and binds currentStore - never override it here.
        if (app()->bound('currentStore') && app('currentStore')) {
            return $next($request);
        }
        $lang = $request->cookie('lang');
        app()->setLocale(in_array($lang, ['bn', 'en'], true) ? $lang : 'bn');

        return $next($request);
    }
}
