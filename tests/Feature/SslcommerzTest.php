<?php

namespace Tests\Feature;

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SslcommerzTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    private function product(User $u): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->orderBy('id')->firstOrFail();
    }

    private function url(User $u, string $path): string
    {
        return 'http://'.$u->store->slug.'.'.config('shop.base_domain').$path;
    }

    private function enableSslcommerz(User $u): void
    {
        $this->actingAs($u)->put(route('seller.settings.update'), [
            'name' => $u->store->name, 'delivery_charge' => 60,
            'sslcommerz_on' => '1', 'sslcommerz_store_id' => 'test_store', 'sslcommerz_store_passwd' => 'test_pass', 'sslcommerz_sandbox' => '1',
        ])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
    }

    public function test_seller_can_enable_sslcommerz_and_password_is_masked_on_resave(): void
    {
        $u = $this->seller();
        $this->enableSslcommerz($u);

        $m = PaymentMethod::withoutGlobalScopes()->where('type', 'sslcommerz')->firstOrFail();
        $this->assertTrue($m->is_active);
        $this->assertSame('test_store', $m->config['store_id']);
        $this->assertSame('test_pass', $m->secret);

        // re-saving without typing a new password keeps the old one
        $this->actingAs($u)->put(route('seller.settings.update'), [
            'name' => $u->store->name, 'delivery_charge' => 60,
            'sslcommerz_on' => '1', 'sslcommerz_store_id' => 'test_store', 'sslcommerz_sandbox' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('test_pass', $m->fresh()->secret);
    }

    public function test_checkout_redirects_to_the_gateway_page_url(): void
    {
        Http::fake(['*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox-gw.sslcommerz.com/pay/abc'])]);
        $u = $this->seller();
        $this->enableSslcommerz($u);
        $p = $this->product($u);

        $res = $this->withSession(['cart.'.$u->store->id => [$p->id => 1]])->post($this->url($u, '/checkout'), [
            'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'sslcommerz',
        ]);
        $res->assertRedirect('https://sandbox-gw.sslcommerz.com/pay/abc');
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame('unpaid', $order->payment_status);

        Http::assertSent(fn ($r) => $r['store_id'] === 'test_store' && $r['tran_id'] === $order->token);
    }

    public function test_session_init_failure_falls_back_to_the_order_page(): void
    {
        Http::fake(['*/gwprocess/v4/api.php' => Http::response(['status' => 'FAILED', 'failedreason' => 'bad credentials'])]);
        $u = $this->seller();
        $this->enableSslcommerz($u);
        $p = $this->product($u);

        $res = $this->withSession(['cart.'.$u->store->id => [$p->id => 1]])->post($this->url($u, '/checkout'), [
            'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'sslcommerz',
        ]);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        $res->assertRedirect('/order/'.$order->token);
    }

    public function test_success_callback_validates_server_side_before_marking_paid(): void
    {
        $u = $this->seller();
        $this->enableSslcommerz($u);
        $p = $this->product($u);
        app()->instance('currentStore', $u->store->fresh());
        $order = Order::create([
            'number' => 'T1', 'token' => str_repeat('a', 40), 'status' => 'pending', 'payment_method' => 'sslcommerz',
            'payment_status' => 'unpaid', 'customer_name' => 'Karim', 'customer_phone' => '01712345678', 'address' => 'Dhaka',
            'currency' => 'BDT', 'subtotal' => 500, 'delivery_charge' => 60, 'total' => 560,
        ]);
        app()->forgetInstance('currentStore');

        // one fake that distinguishes by val_id - calling Http::fake() twice in one test
        // ADDS stubs rather than replacing them, so the first-registered one would always win.
        Http::fake(fn ($request) => $request['val_id'] === 'fake'
            ? Http::response(['status' => 'INVALID'])
            : Http::response(['status' => 'VALID', 'amount' => '560.00', 'currency' => 'BDT', 'bank_tran_id' => 'BANK123']));

        // a spoofed/invalid val_id must NOT mark the order paid
        $this->get($this->url($u, '/sslcommerz/success/'.$order->token.'?val_id=fake'))->assertRedirect('/order/'.$order->token);
        $this->assertSame('unpaid', $order->fresh()->payment_status);

        // a genuinely valid, amount-matching response marks it paid
        $this->get($this->url($u, '/sslcommerz/success/'.$order->token.'?val_id=real'))->assertRedirect('/order/'.$order->token);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('BANK123', $order->fresh()->trx_id);
    }

    public function test_ipn_webhook_confirms_payment_without_a_csrf_token(): void
    {
        $u = $this->seller();
        $this->enableSslcommerz($u);
        app()->instance('currentStore', $u->store->fresh());
        $order = Order::create([
            'number' => 'T2', 'token' => str_repeat('b', 40), 'status' => 'pending', 'payment_method' => 'sslcommerz',
            'payment_status' => 'unpaid', 'customer_name' => 'Karim', 'customer_phone' => '01712345678', 'address' => 'Dhaka',
            'currency' => 'BDT', 'subtotal' => 500, 'delivery_charge' => 60, 'total' => 560,
        ]);
        app()->forgetInstance('currentStore');

        Http::fake(['*/validationserverAPI.php*' => Http::response(['status' => 'VALID', 'amount' => '560.00', 'currency' => 'BDT'])]);
        $this->post($this->url($u, '/sslcommerz/ipn'), ['tran_id' => $order->token, 'val_id' => 'real'])->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_cancel_leaves_the_order_unpaid(): void
    {
        $u = $this->seller();
        app()->instance('currentStore', $u->store->fresh());
        $order = Order::create([
            'number' => 'T3', 'token' => str_repeat('c', 40), 'status' => 'pending', 'payment_method' => 'sslcommerz',
            'payment_status' => 'unpaid', 'customer_name' => 'Karim', 'customer_phone' => '01712345678', 'address' => 'Dhaka',
            'currency' => 'BDT', 'subtotal' => 500, 'delivery_charge' => 60, 'total' => 560,
        ]);
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/sslcommerz/cancel/'.$order->token))->assertRedirect('/order/'.$order->token);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_capi_purchase_event_only_dispatches_after_payment_is_confirmed_not_at_order_creation(): void
    {
        Bus::fake();
        Http::fake(['*/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox-gw.sslcommerz.com/pay/abc'])]);
        $u = $this->seller();
        $this->enableSslcommerz($u);
        $p = $this->product($u);
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Tracked', 'is_published' => '1',
            'pixel_id' => '1234567890123456', 'capi_token' => 'EAAtest',
        ]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->post($this->url($u, '/lp/tracked/order'), [
            'qty' => 1, 'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'sslcommerz',
        ]);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        Bus::assertNotDispatched(SendMetaPurchaseEvent::class); // payment not confirmed yet - must not fire

        Http::fake(['*/validationserverAPI.php*' => Http::response(['status' => 'VALID', 'amount' => (string) $order->total, 'currency' => 'BDT'])]);
        $this->get($this->url($u, '/sslcommerz/success/'.$order->token.'?val_id=real'));
        Bus::assertDispatched(SendMetaPurchaseEvent::class); // now it's a real, paid conversion
    }
}
