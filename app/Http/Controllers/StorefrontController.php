<?php

namespace App\Http\Controllers;

use App\Models\Product;

class StorefrontController extends Controller
{
    public function home()
    {
        return view('storefront.home', [
            'store' => app('currentStore'),
            'products' => Product::with('images')->where('is_active', true)->latest()->get(),
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
