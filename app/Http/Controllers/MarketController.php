<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    /** Active products of published stores only. Tenant scopes are lifted on purpose: this is the public marketplace. */
    private function products()
    {
        return Product::withoutGlobalScopes()
            ->with(['store', 'images' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('is_active', true)
            ->whereHas('store', fn ($q) => $q->where('status', 'published'));
    }

    public function home()
    {
        $stores = Store::where('status', 'published')->latest()->limit(8)->get();
        $counts = Product::withoutGlobalScopes()->whereIn('store_id', $stores->pluck('id'))->where('is_active', true)
            ->selectRaw('store_id, count(*) c')->groupBy('store_id')->pluck('c', 'store_id');

        return view('welcome', [
            'deals' => $this->products()->whereNotNull('sale_price')->where('stock', '>', 0)->latest()->limit(6)->get(),
            'new' => $this->products()->latest()->limit(8)->get(),
            'stores' => $stores,
            'counts' => $counts,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $like = '%'.addcslashes(mb_substr($q, 0, 60), '%_\\').'%';
        $results = $q === ''
            ? collect()
            : $this->products()->where('name', 'like', $like)->latest()->paginate(24)->withQueryString();

        return view('market.search', ['q' => $q, 'results' => $results]);
    }

    public function lang(string $locale)
    {
        abort_unless(in_array($locale, ['bn', 'en'], true), 404);

        return redirect()->back(fallback: '/')->withCookie(cookie('lang', $locale, 60 * 24 * 365));
    }
}
