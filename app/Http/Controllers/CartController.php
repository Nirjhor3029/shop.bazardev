<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cart(): Cart
    {
        return new Cart(app('currentStore')->id);
    }

    public function show()
    {
        $cart = $this->cart();

        return view('storefront.cart', ['store' => app('currentStore'), 'lines' => $cart->lines(), 'subtotal' => $cart->subtotal()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'qty' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $p = Product::where('is_active', true)->findOrFail($data['product_id']);
        abort_if($p->stock < 1, 422, 'Out of stock.');
        $this->cart()->add($p->id, $data['qty'] ?? 1);

        return redirect('/cart')->with('status', 'Added to cart.');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['qty' => ['required', 'array'], 'qty.*' => ['integer', 'min:0', 'max:99']]);
        foreach ($data['qty'] as $id => $qty) {
            $this->cart()->set((int) $id, (int) $qty);
        }

        return redirect('/cart');
    }
}
