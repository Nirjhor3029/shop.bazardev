<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
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
        $data = $request->validate([
            'product_id' => ['required', 'integer'], 'variant_id' => ['nullable', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $p = Product::where('is_active', true)->findOrFail($data['product_id']);
        $variant = null;
        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::where('product_id', $p->id)->findOrFail($data['variant_id']);
        }
        abort_if($p->stockFor($variant) < 1, 422, 'Out of stock.');
        $this->cart()->add($p->id, $data['qty'] ?? 1, $variant?->id);

        return redirect(app('currentStore')->storePath('/cart'))->with('status', __('store.added'));
    }

    /** "Frequently bought together" - add several products at once, no variant selection (V1). */
    public function addBundle(Request $request)
    {
        $data = $request->validate(['product_ids' => ['required', 'array', 'max:5'], 'product_ids.*' => ['integer']]);
        $products = Product::with('variants')->where('is_active', true)->whereIn('id', $data['product_ids'])->get();
        $cart = $this->cart();
        foreach ($products as $p) {
            // No variant picker in the bundle strip (V1), so a product that needs one can't be bulk-added safely.
            if ($p->variants->isEmpty() && $p->stock > 0) {
                $cart->add($p->id, 1);
            }
        }

        return redirect(app('currentStore')->storePath('/cart'))->with('status', __('store.added'));
    }

    public function update(Request $request)
    {
        $data = $request->validate(['qty' => ['required', 'array'], 'qty.*' => ['integer', 'min:0', 'max:99']]);
        foreach ($data['qty'] as $key => $qty) {
            [$productId, $variantId] = Cart::parseKey((string) $key);
            $this->cart()->set($productId, (int) $qty, $variantId);
        }

        return redirect(app('currentStore')->storePath('/cart'));
    }
}
