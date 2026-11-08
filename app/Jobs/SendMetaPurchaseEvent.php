<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors a landing-page order as a Meta Conversions API "Purchase" event, using the order
 * token as event_id so it dedupes against the browser-side Pixel event for the same order.
 * Fires on order placement (not on later payment confirmation), matching how COD conversions
 * are tracked elsewhere - a correction event for cancelled/returned orders is a future step.
 */
class SendMetaPurchaseEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private int $orderId) {}

    public function handle(): void
    {
        $order = Order::withoutGlobalScopes()->with('landingPage')->find($this->orderId);
        $lp = $order?->landingPage;
        if (! $order || ! $lp || ! $lp->pixel_id || ! $lp->capi_token) {
            return;
        }

        $userData = ['ph' => [hash('sha256', $this->normalizedPhone($order->customer_phone))]];
        if ($order->customer_email) {
            $userData['em'] = [hash('sha256', strtolower(trim($order->customer_email)))];
        }
        if ($order->fbclid) {
            $userData['fbc'] = 'fb.1.'.($order->created_at->timestamp * 1000).'.'.$order->fbclid;
        }
        if ($order->fbp) {
            $userData['fbp'] = $order->fbp;
        }

        $payload = [
            'access_token' => $lp->capi_token,
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => $order->created_at->timestamp,
                'event_id' => $order->token,
                'action_source' => 'website',
                'event_source_url' => $lp->url(),
                'user_data' => $userData,
                'custom_data' => ['currency' => $order->currency, 'value' => (float) $order->total],
            ]],
        ];

        try {
            $res = Http::timeout(5)->post("https://graph.facebook.com/v19.0/{$lp->pixel_id}/events", $payload);
            if ($res->failed()) {
                Log::warning('Meta CAPI purchase event rejected', ['order_id' => $order->id, 'response' => $res->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI purchase event failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** Meta expects digits only, country code included (BD local 01XXXXXXXXX -> 8801XXXXXXXXX), then SHA-256 hashed. */
    private function normalizedPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '0') ? '88'.$digits : $digits;
    }
}
