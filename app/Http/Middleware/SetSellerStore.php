<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Seller area (main domain): tenant = the logged-in user's own store. */
class SetSellerStore
{
    public function handle(Request $request, Closure $next)
    {
        $store = $request->user()?->store;
        if (! $store) {
            return redirect()->route('store.create');
        }
        app()->instance('currentStore', $store);

        return $next($request);
    }
}
