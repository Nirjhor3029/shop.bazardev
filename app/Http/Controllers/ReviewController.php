<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Public, no-login review submission - gated by the order's secret token, so only a real (and delivered) buyer can leave one. */
    public function store(Request $request, string $token, Product $product)
    {
        $order = Order::with('items')->where('token', $token)->firstOrFail();
        abort_unless($order->items->contains('product_id', $product->id), 403);

        if ($order->status !== 'delivered') {
            return back()->with('status', __('store.review_not_delivered'));
        }
        if ($order->reviews()->where('product_id', $product->id)->exists()) {
            return back()->with('status', __('store.review_already_sent'));
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order->reviews()->create([
                'product_id' => $product->id,
                'customer_name' => $order->customer_name,
                'rating' => $data['rating'],
                'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            ]);
        } catch (QueryException) {
            return back()->with('status', __('store.review_already_sent')); // lost a race with a duplicate submit
        }

        return back()->with('status', __('store.review_sent'));
    }
}
