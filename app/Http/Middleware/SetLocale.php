<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Marketplace + auth pages: Bangla by default, English if the visitor chose it (cookie `lang`). */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->cookie('lang');
        app()->setLocale(in_array($lang, ['bn', 'en'], true) ? $lang : 'bn');

        return $next($request);
    }
}
