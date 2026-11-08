<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PaymentMethod;
use App\Services\OrderPlacer;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function show(string $slug)
    {
        $store = app('currentStore');
        $lp = LandingPage::where('slug', $slug)->where('is_published', true)->with('product.images')->firstOrFail();
        $sub = (float) $lp->product->currentPrice();

        return view('landing.show', [
            'store' => $store, 'lp' => $lp, 'product' => $lp->product,
            'delivery' => OrderPlacer::deliveryCharge($store, $sub),
            'methods' => PaymentMethod::where('is_active', true)->get(),
        ]);
    }

    public function order(Request $request, string $slug, OrderPlacer $placer)
    {
        $store = app('currentStore');
        $lp = LandingPage::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.min(99, max(1, $lp->product->stock))],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,bkash,nagad,rocket'],
            'trx_id' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9]+$/'],
            'payer_number' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
        ], ['phone.regex' => 'Enter a valid mobile number like 01712345678.', 'payer_number.regex' => 'Enter the number you sent money from.']);

        $order = $placer->placeSingle($store, $lp->product, (int) $data['qty'], $data, $lp);

        return redirect('/order/'.$order->token);
    }
}
