<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Services\StoreCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public static function slugProblem(string $s, ?int $ignoreId = null): ?string
    {
        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,28}[a-z0-9]$/', $s)) {
            return 'Use 3-30 letters (a-z), numbers or dash. Cannot start or end with a dash.';
        }
        if (in_array($s, config('shop.reserved'), true)) {
            return 'This name is reserved.';
        }
        if (Store::where('slug', $s)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            return 'Already taken.';
        }

        return null;
    }

    public function check(Request $request): JsonResponse
    {
        $p = self::slugProblem(strtolower((string) $request->query('s')));

        return response()->json(['ok' => $p === null, 'message' => $p ?? 'Available']);
    }

    public function create(Request $request)
    {
        return $request->user()->store ? redirect()->route('seller.dashboard') : view('seller.create');
    }

    public function store(Request $request, StoreCreator $creator)
    {
        abort_if($request->user()->store, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'slug' => ['required', 'string']]);
        $slug = strtolower($data['slug']);
        if ($p = self::slugProblem($slug)) {
            return back()->withInput()->withErrors(['slug' => $p]);
        }
        $creator->create($request->user(), $slug, $data['name']);

        return redirect()->route('seller.dashboard')->with('status', 'Your store is ready!');
    }

    public function dashboard(Request $request)
    {
        $store = app('currentStore');

        return view('seller.dashboard', [
            'store' => $store,
            'products' => Product::count(),
            'hasRealProduct' => Product::where('is_sample', false)->exists(),
        ]);
    }

    public function publish(Request $request)
    {
        $store = app('currentStore');
        abort_unless($request->user()->hasVerifiedEmail(), 403);
        $store->update(['status' => $request->boolean('publish') ? 'published' : 'draft']);

        return back()->with('status', $store->status === 'published' ? 'Store is live.' : 'Store is now hidden.');
    }
}
