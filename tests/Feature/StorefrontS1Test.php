<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use App\Support\Fmt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontS1Test extends TestCase
{
    use RefreshDatabase;

    private function store(array $look = [], array $settings = [])
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, 'alpha', 'Alpha');
        $s->update(['status' => 'published', 'settings' => array_merge($s->settings, $settings, ['look' => $look])]);

        return $s->fresh();
    }

    private function url(string $path = '/'): string
    {
        return 'http://alpha.'.config('shop.base_domain').$path;
    }

    private function first(): Product
    {
        return Product::withoutGlobalScopes()->orderBy('id')->first();
    }

    public function test_bangla_digits_lakh_grouping_and_english_fallback(): void
    {
        $this->store();
        app()->instance('currentStore', \App\Models\Store::first());
        app()->setLocale('bn');
        $this->assertSame('১,৫০,০০০', Fmt::number(150000));
        $this->assertSame('৳১,৫০০', Fmt::money(1500));
        app()->setLocale('en');
        $this->assertSame('150,000', Fmt::number(150000));
        app()->setLocale('bn');
        $s = \App\Models\Store::first();
        $s->update(['settings' => array_merge($s->settings, ['look' => ['digits' => false]])]);
        app()->instance('currentStore', $s->fresh());
        $this->assertSame('৳1,500', Fmt::money(1500)); // digits off: latin digits, still taka sign
    }

    public function test_whatsapp_number_normalising(): void
    {
        $this->assertSame('8801712345678', Fmt::whatsapp('01712-345678'));
        $this->assertSame('8801712345678', Fmt::whatsapp('+8801712345678'));
        $this->assertNull(Fmt::whatsapp('12345'));
        $this->assertNull(Fmt::whatsapp(null));
    }

    public function test_store_defaults_to_bangla_and_can_be_english(): void
    {
        $this->store();
        $this->get($this->url('/cart'))->assertOk()->assertSee('আপনার কার্ট');
        \App\Models\Store::first()->update(['settings' => ['look' => ['lang' => 'en']]]);
        $this->get($this->url('/cart'))->assertOk()->assertSee('Your cart');
    }

    public function test_product_page_has_og_tags_and_no_whatsapp_without_number(): void
    {
        $this->store();
        $p = $this->first();
        $r = $this->get($this->url('/p/'.$p->slug))->assertOk();
        $r->assertSee('property="og:title" content="'.$p->name.'"', false)->assertSee('property="og:type" content="product"', false)
            ->assertSee('product:price:amount', false)->assertSee('og:url', false)->assertSee('sf-bar', false);
        $r->assertDontSee('wa.me/88', false);
    }

    public function test_whatsapp_button_and_message_when_number_set(): void
    {
        $this->store(['whatsapp' => '01712345678']);
        $p = $this->first();
        $r = $this->get($this->url('/p/'.$p->slug))->assertOk();
        $r->assertSee('https://wa.me/8801712345678?text=', false);
        $r->assertSee(rawurlencode('https://alpha.'.config('shop.base_domain').'/p/'.$p->slug), false);   // product link inside the message
        $this->get($this->url('/'))->assertOk()->assertSee('sf-fab', false); // floating button on the home page
    }

    public function test_related_prefers_same_category_and_excludes_self(): void
    {
        $s = $this->store();
        app()->instance('currentStore', $s);
        $c = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
        $a = Product::create(['category_id' => $c->id, 'name' => 'Shoe A', 'slug' => 'shoe-a', 'price' => 10, 'stock' => 5]);
        Product::create(['category_id' => $c->id, 'name' => 'Shoe B', 'slug' => 'shoe-b', 'price' => 10, 'stock' => 5]);
        app()->forgetInstance('currentStore');
        $html = $this->get($this->url('/p/shoe-a'))->assertOk()->getContent();
        $this->assertStringContainsString('Shoe B', $html);
        $this->assertSame(0, substr_count(explode('<section class="mt-12', $html)[1] ?? '', 'href="/p/shoe-a"')); // not listed under itself
    }

    public function test_low_stock_message_and_out_of_stock(): void
    {
        $this->store();
        $p = $this->first();
        $p->update(['stock' => 2]);
        $this->get($this->url('/p/'.$p->slug))->assertOk()->assertSee('মাত্র ২ টি বাকি');
        $p->update(['stock' => 0]);
        $r = $this->get($this->url('/p/'.$p->slug))->assertOk()->assertSee('স্টক শেষ');
        $r->assertDontSee('id="addform"', false);
    }

    public function test_out_of_stock_still_offers_whatsapp_contact(): void
    {
        $this->store(['whatsapp' => '01712345678']);
        $p = $this->first();
        $p->update(['stock' => 0]);
        $r = $this->get($this->url('/p/'.$p->slug))->assertOk();
        $r->assertDontSee('id="addform"', false)->assertSee('স্টক আসলে জানতে WhatsApp করুন');
    }

    public function test_product_page_has_json_ld_and_no_image_placeholder(): void
    {
        $this->store();
        $p = $this->first();
        $p->images()->delete();
        $this->get($this->url('/p/'.$p->slug))->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('ছবি নেই');
    }

    public function test_delivery_line_uses_store_settings_and_payment_chips(): void
    {
        $this->store([], ['delivery_charge' => 60, 'free_delivery_over' => 2000]);
        $p = $this->first();
        $this->get($this->url('/p/'.$p->slug))->assertOk()->assertSee('৳৬০ · ৳২,০০০ এর বেশি হলে ফ্রি')->assertSee('ক্যাশ অন ডেলিভারি');
    }

    public function test_featured_products_show_in_spotlight_strip(): void
    {
        $this->store();
        $p = $this->first();
        $this->get($this->url('/'))->assertOk()->assertDontSee('নির্বাচিত পণ্য');

        $p->update(['is_featured' => true]);
        $this->get($this->url('/'))->assertOk()->assertSee('নির্বাচিত পণ্য');
    }

    public function test_appearance_saves_whatsapp_language_digits_and_rejects_bad_number(): void
    {
        $s = $this->store();
        $u = $s->user;
        $this->actingAs($u)->post(route('seller.appearance.update'), [
            'theme' => 'minimal', 'color' => '#112233', 'font' => 'sans', 'lang' => 'en', 'whatsapp' => '01712345678',
            'sections' => ['hero' => ['order' => 1, 'on' => 1]],
        ])->assertSessionHasNoErrors();
        $look = $s->fresh()->look();
        $this->assertSame('en', $look['lang']);
        $this->assertSame('01712345678', $look['whatsapp']);
        $this->assertFalse($look['digits']); // checkbox not sent = off

        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($u)->post(route('seller.appearance.update'), ['theme' => 'minimal', 'color' => '#112233', 'font' => 'sans', 'whatsapp' => 'abc', 'sections' => ['hero' => ['on' => 1]]])
            ->assertSessionHasErrors('whatsapp');
    }
}
