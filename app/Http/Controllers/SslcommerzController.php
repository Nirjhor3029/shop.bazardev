<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\SslcommerzGateway;
use Illuminate\Http\Request;

class SslcommerzController extends Controller
{
    public function success(Request $request, string $token, SslcommerzGateway $gateway)
    {
        $order = Order::where('token', $token)->firstOrFail();
        if ($valId = $request->input('val_id')) {
            $method = PaymentMethod::where('type', 'sslcommerz')->first();
            if ($method) {
                $gateway->confirm($method, $order, $valId);
            }
        }

        return redirect(app('currentStore')->storePath('/order/'.$order->token));
    }

    public function fail(string $token)
    {
        $order = Order::where('token', $token)->firstOrFail();
        if ($order->payment_status === 'unpaid') {
            $order->update(['payment_status' => 'failed']);
        }

        return redirect(app('currentStore')->storePath('/order/'.$order->token));
    }

    public function cancel(string $token)
    {
        // left as unpaid - the buyer can retry or pick another method by contacting the seller
        return redirect(app('currentStore')->storePath('/order/'.$token));
    }

    /**
     * SSLCommerz posts here server-to-server - the reliable confirmation path (the browser
     * redirect alone can be skipped or spoofed). The ipn_url we register is itself under the
     * store's subdomain, so `store.host` middleware has already scoped this request's queries.
     */
    public function ipn(Request $request, SslcommerzGateway $gateway)
    {
        $order = Order::where('token', $request->input('tran_id'))->first();
        $valId = $request->input('val_id');
        if ($order && $valId) {
            $method = PaymentMethod::where('type', 'sslcommerz')->first();
            if ($method) {
                $gateway->confirm($method, $order, $valId);
            }
        }

        return response('OK');
    }
}
