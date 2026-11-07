<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Visitors who entered through the demo button cannot change the account or take the store offline. */
class ProtectDemoSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && config('app.demo_mode') && $request->session()->get('demo_user_id') === $user->id) {
            $blocked = $request->is('profile*', 'password*', 'logout-all*', 'seller/publish', 'store/create');
            if ($blocked && ! $request->isMethodSafe()) {
                return back()->with('status', 'This is a shared demo: that action is turned off.');
            }
        }

        return $next($request);
    }
}
