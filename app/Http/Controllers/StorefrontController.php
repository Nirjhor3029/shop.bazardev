<?php

namespace App\Http\Controllers;

use App\Models\Product;

class StorefrontController extends Controller
{
    public function home(\Illuminate\Http\Request $request)
    {
        $store = app('currentStore');
        $categories = \App\Models\Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('name')->get()->filter(fn ($c) => $c->products_count > 0)->values();
        $active = $categories->firstWhere('slug', (string) $request->query('c'));

        return view('storefront.home', [
            'store' => $store,
            'categories' => $categories,
            'activeCategory' => $active,
            'products' => Product::with('images')->where('is_active', true)->when($active, fn ($q) => $q->where('category_id', $active->id))->latest()->get(),
            'featured' => Product::with('images')->where('is_active', true)->where('is_featured', true)->latest()->limit(8)->get(),
            'methods' => \App\Models\PaymentMethod::where('is_active', true)->pluck('type'),
        ]);
    }

    public function product(string $slug)
    {
        $store = app('currentStore');
        $product = Product::with('images')->where('is_active', true)->where('slug', $slug)->firstOrFail();
        $related = Product::with('images')->where('is_active', true)->where('id', '!=', $product->id)
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id])->latest()->limit(4)->get();

        return view('storefront.product', [
            'store' => $store, 'product' => $product, 'related' => $related,
            'methods' => \App\Models\PaymentMethod::where('is_active', true)->pluck('type'),
        ]);
    }

    /** Client-only wishlist page - the list itself lives in the viewer's localStorage, fetched via /cards. */
    public function wishlist()
    {
        return view('storefront.wishlist', ['store' => app('currentStore')]);
    }

    /**
     * Renders product cards for a given set of ids, in the order requested. Backs both the
     * wishlist page and the "recently viewed" strip - both are pure client-side (localStorage)
     * features with no server-side list of their own, so this is the only query either needs.
     */
    public function cardsByIds(\Illuminate\Http\Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))->filter()->unique()->take(20)->values();

        $products = Product::with('images')->where('is_active', true)->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => $ids->search($p->id))->values();

        return view('storefront.cards', ['store' => app('currentStore'), 'products' => $products]);
    }
}
