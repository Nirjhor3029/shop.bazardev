<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\OrderPlacer;
use App\Services\SslcommerzGateway;
use App\Support\Cart;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show()
    {
        $store = app('currentStore');
        $cart = new Cart($store->id);
        if ($cart->lines()->isEmpty()) {
            return redirect('/cart');
        }
        $sub = $cart->subtotal();

        return view('storefront.checkout', [
            'store' => $store, 'lines' => $cart->lines(), 'subtotal' => $sub,
            'delivery' => OrderPlacer::deliveryCharge($store, $sub),
            'methods' => PaymentMethod::where('is_active', true)->get(),
        ]);
    }

    public function place(Request $request, OrderPlacer $placer, SslcommerzGateway $gateway)
    {
        $store = app('currentStore');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,bkash,nagad,rocket,sslcommerz'],
            'trx_id' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9]+$/'],
            'payer_number' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
        ], ['phone.regex' => 'Enter a valid mobile number like 01712345678.', 'payer_number.regex' => 'Enter the number you sent money from.']);

        $order = $placer->place($store, new Cart($store->id), $data);

        if ($order->payment_method === 'sslcommerz') {
            $method = PaymentMethod::where('type', 'sslcommerz')->first();
            if ($method && ($url = $gateway->initiate($store, $method, $order))) {
                return redirect()->away($url);
            }
        }

        return redirect('/order/'.$order->token);
    }

    public function order(string $token)
    {
        $order = Order::with(['items', 'landingPage'])->where('token', $token)->firstOrFail();
        $method = PaymentMethod::where('type', $order->payment_method)->first();

        return view('storefront.order', ['store' => app('currentStore'), 'order' => $order, 'method' => $method]);
    }
}
