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
        ]);
    }

    public function product(string $slug)
    {
        return view('storefront.product', [
            'store' => app('currentStore'),
            'product' => Product::with('images')->where('is_active', true)->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
