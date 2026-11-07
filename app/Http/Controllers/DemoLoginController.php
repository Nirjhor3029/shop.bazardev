<?php

namespace App\Http\Controllers;

use App\Services\DemoStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(config('app.demo_mode'), 404);
        $user = DemoStore::ensure();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('demo_user_id', $user->id);

        return redirect()->route('seller.dashboard');
    }
}
