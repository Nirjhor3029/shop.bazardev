<?php

namespace App\Services;

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Store;
use App\Support\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderPlacer
{
    public static function deliveryCharge(Store $store, float $subtotal): float
    {
        $s = $store->settings ?? [];
        $free = (float) ($s['free_delivery_over'] ?? 0);
        if ($free > 0 && $subtotal >= $free) {
            return 0.0;
        }

        return round((float) ($s['delivery_charge'] ?? 0), 2);
    }

    /** @param array{name:string,phone:string,email:?string,address:string,note:?string,payment_method:string,trx_id:?string,payer_number:?string} $d */
    public function place(Store $store, Cart $cart, array $d): Order
    {
        $raw = $cart->raw();
        if (! $raw) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }
        $order = $this->createOrder($store, $raw, $d, null);
        $cart->clear();

        return $order;
    }

    /**
     * Buy-now flow for a single product (landing pages): no session cart involved.
     * $comboPrice, when given, is a seller-configured bundle total for this exact $qty
     * (e.g. "2 for 900"), looked up server-side - never a price the buyer can submit.
     */
    public function placeSingle(Store $store, Product $product, int $qty, array $d, ?LandingPage $landingPage = null, ?float $comboPrice = null): Order
    {
        $order = $this->createOrder($store, [$product->id => $qty], $d, $landingPage, $comboPrice);
        // sslcommerz hasn't been paid yet at this point - SslcommerzGateway::confirm() dispatches this
        // itself once the payment is actually validated, so a failed/abandoned payment is never counted.
        if ($order->payment_method !== 'sslcommerz' && $landingPage && $landingPage->pixel_id && $landingPage->capi_token) {
            SendMetaPurchaseEvent::dispatch($order->id);
        }

        return $order;
    }

    private function createOrder(Store $store, array $raw, array $d, ?LandingPage $landingPage, ?float $comboPrice = null): Order
    {
        return DB::transaction(function () use ($store, $raw, $d, $landingPage, $comboPrice) {
            $method = PaymentMethod::where('type', $d['payment_method'])->where('is_active', true)->first();
            if (! $method) {
                throw ValidationException::withMessages(['payment_method' => 'This payment method is not available.']);
            }
            $manual = in_array($method->type, ['bkash', 'nagad', 'rocket'], true);
            if ($manual && (blank($d['trx_id'] ?? null) || blank($d['payer_number'] ?? null))) {
                throw ValidationException::withMessages(['trx_id' => 'Send the money first, then enter the Transaction ID and your number.']);
            }

            // lock the rows so two buyers cannot take the last item together
            $products = Product::where('is_active', true)->whereIn('id', array_keys($raw))->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0.0;
            $rows = [];
            foreach ($raw as $id => $qty) {
                $p = $products->get($id);
                if (! $p || $p->stock < $qty) {
                    throw ValidationException::withMessages(['cart' => ($p?->name ?? 'A product').' is out of stock or has less stock than you asked.']);
                }
                $line = $comboPrice !== null ? round($comboPrice, 2) : round((float) $p->currentPrice() * $qty, 2);
                $price = round($line / $qty, 2);
                $subtotal += $line;
                $rows[] = [$p, $qty, $price, $line];
            }
            $subtotal = round($subtotal, 2);
            $delivery = self::deliveryCharge($store, $subtotal);

            $order = Order::create([
                'number' => $this->number(),
                'token' => Str::random(40),
                'status' => 'pending',
                'source' => $landingPage ? 'landing' : 'storefront',
                'landing_page_id' => $landingPage?->id,
                'fbclid' => $d['fbclid'] ?? null,
                'fbp' => $d['fbp'] ?? null,
                'payment_method' => $method->type,
                'payment_status' => $manual ? 'verifying' : 'unpaid',
                'trx_id' => $manual ? $d['trx_id'] : null,
                'payer_number' => $manual ? $d['payer_number'] : null,
                'customer_name' => $d['name'],
                'customer_phone' => $d['phone'],
                'customer_email' => $d['email'] ?? null,
                'address' => $d['address'],
                'note' => $d['note'] ?? null,
                'currency' => $store->settings['currency'] ?? 'BDT',
                'subtotal' => $subtotal,
                'delivery_charge' => $delivery,
                'total' => round($subtotal + $delivery, 2),
            ]);
            foreach ($rows as [$p, $qty, $price, $line]) {
                $order->items()->create(['product_id' => $p->id, 'name' => $p->name, 'unit_price' => $price, 'quantity' => $qty, 'line_total' => $line]);
                $p->decrement('stock', $qty);
            }

            return $order;
        });
    }

    private function number(): string
    {
        do {
            $n = 'S'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::withoutGlobalScopes()->where('number', $n)->exists());

        return $n;
    }

    /** Seller changes the status. Cancelling gives the stock back. */
    public static function changeStatus(Order $order, string $to): void
    {
        abort_unless(in_array($to, $order->nextStatuses(), true), 422, 'This change is not allowed.');
        DB::transaction(function () use ($order, $to) {
            if ($to === 'cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                }
            }
            $order->update(['status' => $to]);
        });
    }
}
