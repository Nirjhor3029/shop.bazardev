<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PaymentMethod;
use App\Services\OrderPlacer;
use App\Services\SslcommerzGateway;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $store = app('currentStore');
        $lp = LandingPage::where('slug', $slug)->where('is_published', true)->with('product.images')->firstOrFail();
        $sub = (float) $lp->product->currentPrice();

        return view('landing.show', [
            'store' => $store, 'lp' => $lp, 'product' => $lp->product,
            'delivery' => OrderPlacer::deliveryCharge($store, $sub),
            'methods' => PaymentMethod::where('is_active', true)->get(),
            'fbclid' => $request->query('fbclid'),
        ]);
    }

    public function order(Request $request, string $slug, OrderPlacer $placer, SslcommerzGateway $gateway)
    {
        $store = app('currentStore');
        $lp = LandingPage::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $isCombo = $lp->template === 'combo' && ! empty($lp->combos);

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,bkash,nagad,rocket,sslcommerz'],
            'trx_id' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9]+$/'],
            'payer_number' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'fbclid' => ['nullable', 'string', 'max:120'],
        ];
        $rules[$isCombo ? 'combo' : 'qty'] = $isCombo
            ? ['required', 'integer', 'min:0', 'max:'.(count($lp->combos) - 1)]
            : ['required', 'integer', 'min:1', 'max:'.min(99, max(1, $lp->product->stock))];

        $data = $request->validate($rules, ['phone.regex' => 'Enter a valid mobile number like 01712345678.', 'payer_number.regex' => 'Enter the number you sent money from.']);
        $data['fbp'] = $request->cookie('_fbp'); // set by the Meta Pixel script that ran when this page loaded

        // combo price/qty always come from the seller's own saved rows - never from the request.
        [$qty, $comboPrice] = $isCombo
            ? [(int) $lp->combo((int) $data['combo'])['qty'], (float) $lp->combo((int) $data['combo'])['price']]
            : [(int) $data['qty'], null];

        $order = $placer->placeSingle($store, $lp->product, $qty, $data, $lp, $comboPrice);

        if ($order->payment_method === 'sslcommerz') {
            $method = PaymentMethod::where('type', 'sslcommerz')->first();
            if ($method && ($url = $gateway->initiate($store, $method, $order))) {
                return redirect()->away($url);
            }
        }

        return redirect($store->storePath('/order/'.$order->token));
    }
}
