<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
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

    public function test_seller_can_create_and_publish_a_landing_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u);

        $this->actingAs($u)->get(route('seller.landing.create'))->assertOk()->assertSee('Choose a product', false);
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'headline' => 'Eid Combo Offer', 'subheadline' => 'Limited stock',
            'benefits' => "Free delivery\n7-day return", 'is_published' => '1',
        ])->assertRedirect(route('seller.landing.index'));

        $lp = LandingPage::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($p->id, $lp->product_id);
        $this->assertSame('eid-combo-offer', $lp->slug);
        $this->assertTrue($lp->is_published);
        $this->assertSame(['Free delivery', '7-day return'], $lp->benefits);
    }

    public function test_seller_cannot_point_a_landing_page_at_another_stores_product(): void
    {
        $a = $this->seller('alpha');
        $this->seller('beta');
        $foreign = Product::withoutGlobalScopes()->where('store_id', '!=', $a->store->id)->firstOrFail();

        $this->actingAs($a)->post(route('seller.landing.store'), [
            'product_id' => $foreign->id, 'headline' => 'Steal', 'is_published' => '1',
        ])->assertSessionHasErrors('product_id');
    }

    public function test_unpublished_landing_page_is_not_public(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        app()->instance('currentStore', $u->store);
        LandingPage::create(['product_id' => $p->id, 'slug' => 'draft-offer', 'is_published' => false]);
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/lp/draft-offer'))->assertNotFound();
    }

    public function test_published_landing_page_shows_and_takes_a_direct_order_without_a_cart(): void
    {
        $u = $this->seller();
        $p = $this->product($u); // price 500, stock 10
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'headline' => 'Eid Offer', 'is_published' => '1',
        ]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/lp/eid-offer'))->assertOk()->assertSee('Eid Offer');

        $res = $this->post($this->url($u, '/lp/eid-offer/order'), [
            'qty' => 2, 'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod',
        ]);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        $res->assertRedirect('/order/'.$order->token);
        $this->assertSame('landing', $order->source);
        $this->assertSame(2, $order->items()->withoutGlobalScopes()->first()->quantity);
        $this->assertSame(1000.0, (float) $order->subtotal);
        $this->assertSame(8, Product::withoutGlobalScopes()->find($p->id)->stock);
        $this->assertSame([], (new Cart($p->store_id))->raw()); // no session cart touched
    }
}
