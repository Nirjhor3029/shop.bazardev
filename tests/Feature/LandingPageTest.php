<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Eid Combo Offer', 'subheadline' => 'Limited stock',
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
            'product_id' => $foreign->id, 'template' => 'classic', 'headline' => 'Steal', 'is_published' => '1',
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
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Eid Offer', 'is_published' => '1',
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

    public function test_combo_order_uses_the_servers_stored_bundle_price_not_the_product_price(): void
    {
        $u = $this->seller();
        $p = $this->product($u); // price 500, stock 10
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'combo', 'headline' => 'Combo Offer', 'is_published' => '1',
            'combo' => [
                ['qty' => 1, 'price' => 500],
                ['qty' => 2, 'price' => 900, 'label' => 'Popular'], // bundle price, not 2x500
            ],
        ])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/lp/combo-offer'))->assertOk()->assertSee('৯০০');

        $res = $this->post($this->url($u, '/lp/combo-offer/order'), [
            'combo' => 1, 'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod',
        ]);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        $res->assertRedirect('/order/'.$order->token);
        $this->assertSame(2, $order->items()->withoutGlobalScopes()->first()->quantity);
        $this->assertSame(900.0, (float) $order->subtotal); // the bundle price, not qty * unit price
    }

    public function test_video_template_embeds_youtube_and_falls_back_to_image(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'video', 'headline' => 'Video Offer', 'is_published' => '1',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/lp/video-offer'))->assertOk()
            ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_pixel_fires_on_page_view_and_fbclid_is_captured_into_a_hidden_field(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Tracked Offer', 'is_published' => '1',
            'pixel_id' => '1234567890123456',
        ])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/lp/tracked-offer?fbclid=abc123'))->assertOk()
            ->assertSee("fbq('init', '1234567890123456')", false)
            ->assertSee("fbq('track', 'ViewContent'", false)
            ->assertSee('name="fbclid" value="abc123"', false);
    }

    public function test_landing_order_dispatches_a_meta_capi_purchase_event_with_hashed_phone(): void
    {
        Http::fake();
        $u = $this->seller();
        $p = $this->product($u); // price 500, stock 10
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Tracked Offer', 'is_published' => '1',
            'pixel_id' => '1234567890123456', 'capi_token' => 'EAAtestsecret',
        ])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->post($this->url($u, '/lp/tracked-offer/order'), [
            'qty' => 1, 'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod',
            'fbclid' => 'abc123',
        ])->assertStatus(302);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();

        Http::assertSent(function ($request) use ($order) {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v19.0/1234567890123456/events'
                && $body['access_token'] === 'EAAtestsecret'
                && $body['data'][0]['event_id'] === $order->token
                && $body['data'][0]['event_name'] === 'Purchase'
                && $body['data'][0]['user_data']['ph'][0] === hash('sha256', '8801712345678')
                && str_contains($body['data'][0]['user_data']['fbc'], '.abc123');
        });
    }

    public function test_capi_token_is_not_wiped_when_left_blank_on_update(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Keep Token', 'is_published' => '1',
            'capi_token' => 'EAAoriginal',
        ]);
        $lp = LandingPage::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('EAAoriginal', $lp->capi_token);

        $this->actingAs($u)->put(route('seller.landing.update', $lp), [
            'product_id' => $p->id, 'template' => 'classic', 'headline' => 'Keep Token', 'is_published' => '1',
            'capi_token' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('EAAoriginal', $lp->fresh()->capi_token);
    }
}
