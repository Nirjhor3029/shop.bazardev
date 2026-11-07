<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = config('shop.base_domain');
    }

    private function seller(string $slug, array $settings = []): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published', 'settings' => array_merge($s->settings, ['delivery_charge' => 60], $settings)]);

        return $u->fresh();
    }

    private function product(User $u, int $n = 1): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->orderBy('id')->skip($n - 1)->firstOrFail();
    }

    private function url(User $u, string $path): string
    {
        return 'http://'.$u->store->slug.'.'.$this->base.$path;
    }

    private function form(array $over = []): array
    {
        return array_merge(['name' => 'Rahim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod'], $over);
    }

    private function withCart(User $u, array $items)
    {
        return $this->withSession(['cart.'.$u->store->id => $items]);
    }

    public function test_cod_order_totals_stock_and_snapshot(): void
    {
        $a = $this->seller('alpha');
        $p = $this->product($a, 1); // price 500, stock 10
        $res = $this->withCart($a, [$p->id => 3])->post($this->url($a, '/checkout'), $this->form());
        $order = Order::withoutGlobalScopes()->firstOrFail();
        $res->assertRedirect('/order/'.$order->token);
        $this->assertEquals(1500, $order->subtotal);
        $this->assertEquals(60, $order->delivery_charge);
        $this->assertEquals(1560, $order->total);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(7, Product::withoutGlobalScopes()->find($p->id)->stock);
        $this->assertSame('Sample product 1', $order->items()->withoutGlobalScopes()->first()->name);
        $this->get($this->url($a, '/order/'.$order->token))->assertOk()->assertSee($order->number);
    }

    public function test_free_delivery_threshold(): void
    {
        $a = $this->seller('alpha', ['free_delivery_over' => 1000]);
        $this->withCart($a, [$this->product($a, 3)->id => 1])->post($this->url($a, '/checkout'), $this->form()); // 1200
        $this->assertEquals(0, Order::withoutGlobalScopes()->first()->delivery_charge);
    }

    public function test_cannot_buy_more_than_stock_and_nothing_is_created(): void
    {
        $a = $this->seller('alpha');
        $p = $this->product($a);
        $this->withCart($a, [$p->id => 11])->post($this->url($a, '/checkout'), $this->form())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
        $this->assertSame(10, Product::withoutGlobalScopes()->find($p->id)->stock);
    }

    public function test_other_stores_product_cannot_be_added_or_bought(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $bp = $this->product($b);
        $this->post($this->url($a, '/cart/add'), ['product_id' => $bp->id])->assertNotFound();
        // even if a forged session cart holds it, it is ignored
        $this->withCart($a, [$bp->id => 1])->post($this->url($a, '/checkout'), $this->form())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_manual_payment_needs_trx_and_active_method(): void
    {
        $a = $this->seller('alpha');
        $p = $this->product($a);
        $this->withCart($a, [$p->id => 1])->post($this->url($a, '/checkout'), $this->form(['payment_method' => 'bkash']))
            ->assertSessionHasErrors('payment_method'); // bkash is not set up yet
        $this->actingAs($a)->put(route('seller.settings.update'), [
            'name' => 'Alpha', 'color' => '#112233', 'delivery_charge' => 60, 'cod' => 1,
            'pay' => ['bkash' => ['number' => '01811111111']], 'pay_on' => ['bkash' => 1],
        ])->assertSessionHasNoErrors();
        $this->assertTrue((bool) PaymentMethod::withoutGlobalScopes()->where('type', 'bkash')->first()->is_active);

        $this->app['auth']->forgetGuards();
        $this->withCart($a, [$p->id => 1])->post($this->url($a, '/checkout'), $this->form(['payment_method' => 'bkash']))
            ->assertSessionHasErrors('trx_id');
        $this->withCart($a, [$p->id => 1])->post($this->url($a, '/checkout'), $this->form(['payment_method' => 'bkash', 'trx_id' => 'ABC123XY', 'payer_number' => '01922222222']))
            ->assertSessionHasNoErrors();
        $o = Order::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('verifying', $o->payment_status);
    }

    public function test_bad_phone_rejected(): void
    {
        $a = $this->seller('alpha');
        $this->withCart($a, [$this->product($a)->id => 1])->post($this->url($a, '/checkout'), $this->form(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_seller_status_flow_cancel_restores_stock_and_isolation(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $p = $this->product($a);
        $this->withCart($a, [$p->id => 4])->post($this->url($a, '/checkout'), $this->form());
        $o = Order::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(6, Product::withoutGlobalScopes()->find($p->id)->stock);

        $this->app['auth']->forgetGuards();
        $this->actingAs($b)->get(route('seller.orders.show', $o->id))->assertNotFound();
        $this->actingAs($b)->post(route('seller.orders.status', $o->id), ['status' => 'cancelled'])->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->actingAs($a)->post(route('seller.orders.status', $o->id), ['status' => 'delivered'])->assertStatus(422); // skipping steps
        $this->actingAs($a)->post(route('seller.orders.status', $o->id), ['status' => 'confirmed'])->assertRedirect();
        $this->actingAs($a)->post(route('seller.orders.status', $o->id), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(10, Product::withoutGlobalScopes()->find($p->id)->stock);
        $this->actingAs($a)->post(route('seller.orders.status', $o->id), ['status' => 'confirmed'])->assertStatus(422); // final
    }

    public function test_order_token_of_other_store_is_404(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $this->withCart($b, [$this->product($b)->id => 1])->post($this->url($b, '/checkout'), $this->form());
        $o = Order::withoutGlobalScopes()->firstOrFail();
        $this->get($this->url($a, '/order/'.$o->token))->assertNotFound();
        $this->get($this->url($b, '/order/'.$o->token))->assertOk();
    }

    /** Production runs one process per request: no tenant is bound before the request starts. */
    public function test_owner_can_open_own_pages_with_fresh_process_state(): void
    {
        $a = $this->seller('alpha');
        $p = $this->product($a);
        $this->withCart($a, [$p->id => 1])->post($this->url($a, '/checkout'), $this->form());
        $o = Order::withoutGlobalScopes()->firstOrFail();

        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->get(route('seller.products.edit', $p->id))->assertOk();
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->get(route('seller.orders.show', $o->id))->assertOk()->assertSee($o->number);
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->put(route('seller.products.update', $p->id), ['name' => 'Renamed', 'price' => 10, 'stock' => 3])->assertRedirect();
        $this->assertSame('Renamed', Product::withoutGlobalScopes()->find($p->id)->name);
        app()->forgetInstance('currentStore');
        $this->get($this->url($a, '/cart'))->assertOk();
    }

    public function test_new_order_is_unseen_until_seller_opens_it(): void
    {
        $a = $this->seller('alpha');
        $this->withCart($a, [$this->product($a)->id => 1])->post($this->url($a, '/checkout'), $this->form());
        $o = Order::withoutGlobalScopes()->firstOrFail();
        $this->assertNull($o->seller_seen_at);

        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->get(route('seller.orders.index'))->assertOk()->assertSee('1 new');
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->get(route('seller.orders.show', $o->id))->assertOk();
        $this->assertNotNull($o->fresh()->seller_seen_at);
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->get(route('seller.orders.index'))->assertOk()->assertDontSee('1 new');
    }
}
