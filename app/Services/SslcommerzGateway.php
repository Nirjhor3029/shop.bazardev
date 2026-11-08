<?php

namespace App\Services;

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SSLCommerz Session API (payment init) + Order Validation API (anti-spoofing confirmation).
 * Each store plugs in its own store_id/store_passwd (seller's own merchant account) -
 * this platform never holds or moves the money itself.
 * Docs: https://developer.sslcommerz.com/doc/v4/
 */
class SslcommerzGateway
{
    /** Starts a payment session; returns the GatewayPageURL to redirect the buyer to, or null on failure. */
    public function initiate(Store $store, PaymentMethod $method, Order $order): ?string
    {
        $cfg = $method->config ?? [];
        $storeId = $cfg['store_id'] ?? null;
        $passwd = $method->secret;
        if (! $storeId || ! $passwd) {
            return null;
        }
        $url = $store->url();

        try {
            $res = Http::asForm()->timeout(10)->post($this->gatewayBase($cfg).'/gwprocess/v4/api.php', [
                'store_id' => $storeId,
                'store_passwd' => $passwd,
                'total_amount' => (float) $order->total,
                'currency' => $order->currency,
                'tran_id' => $order->token,
                'success_url' => $url.'/sslcommerz/success/'.$order->token,
                'fail_url' => $url.'/sslcommerz/fail/'.$order->token,
                'cancel_url' => $url.'/sslcommerz/cancel/'.$order->token,
                'ipn_url' => $url.'/sslcommerz/ipn',
                'cus_name' => $order->customer_name,
                'cus_email' => $order->customer_email ?: 'no-reply@'.config('shop.base_domain'),
                'cus_phone' => $order->customer_phone,
                'cus_add1' => $order->address,
                'shipping_method' => 'NO',
                'num_of_item' => max(1, $order->items()->count()),
                'product_name' => 'Order '.$order->number,
                'product_category' => 'General',
                'product_profile' => 'general',
            ]);
            $body = $res->json() ?? [];
            if (($body['status'] ?? null) === 'SUCCESS' && ! empty($body['GatewayPageURL'])) {
                return $body['GatewayPageURL'];
            }
            Log::warning('SSLCommerz session init failed', ['order_id' => $order->id, 'reason' => $body['failedreason'] ?? $res->body()]);
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz session init error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Server-to-server confirmation of a val_id. Never trust the browser redirect alone -
     * this is the step that stops a spoofed "success" callback from marking an order paid.
     * Idempotent: safe to call from both the success-redirect and the IPN webhook.
     */
    public function confirm(PaymentMethod $method, Order $order, string $valId): bool
    {
        if ($order->payment_status === 'paid') {
            return true; // already confirmed (e.g. IPN arrived before the browser redirect)
        }
        $cfg = $method->config ?? [];
        $storeId = $cfg['store_id'] ?? null;
        $passwd = $method->secret;
        if (! $storeId || ! $passwd) {
            return false;
        }

        try {
            $res = Http::timeout(10)->get($this->validationBase($cfg).'/validator/api/validationserverAPI.php', [
                'val_id' => $valId, 'store_id' => $storeId, 'store_passwd' => $passwd, 'format' => 'json',
            ]);
            $body = $res->json() ?? [];
            $valid = in_array($body['status'] ?? null, ['VALID', 'VALIDATED'], true)
                && (float) ($body['amount'] ?? 0) >= (float) $order->total - 0.5 // small rounding tolerance
                && ($body['currency'] ?? null) === $order->currency;

            if ($valid) {
                $order->update(['payment_status' => 'paid', 'trx_id' => $body['bank_tran_id'] ?? $valId]);
                $lp = $order->landingPage;
                if ($lp && $lp->pixel_id && $lp->capi_token) {
                    SendMetaPurchaseEvent::dispatch($order->id);
                }

                return true;
            }
            Log::warning('SSLCommerz validation rejected', ['order_id' => $order->id, 'response' => $body]);
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz validation error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return false;
    }

    private function gatewayBase(array $cfg): string
    {
        return ($cfg['sandbox'] ?? true) ? 'https://sandbox-gw.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    private function validationBase(array $cfg): string
    {
        return ($cfg['sandbox'] ?? true) ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }
}
