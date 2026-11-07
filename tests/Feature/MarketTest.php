<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase;

    private function store(string $slug, string $status)
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => $status]);

        return $s;
    }

    public function test_landing_works_with_no_stores_and_defaults_to_bangla(): void
    {
        $this->get('/')->assertOk()->assertSee('এক ক্লিকে খুলুন আপনার অনলাইন দোকান')->assertDontSee('আজকের অফার');
    }

    public function test_language_switch_sets_cookie_and_english_renders(): void
    {
        $this->get('/lang/en')->assertRedirect()->assertCookie('lang', 'en');
        $this->get('/lang/xx')->assertNotFound();
        $this->withCookie('lang', 'en')->get('/')->assertOk()->assertSee('Open your online shop in one click');
    }

    public function test_only_published_stores_products_show_on_landing_and_search(): void
    {
        $live = $this->store('livestore', 'published');
        $draft = $this->store('draftstore', 'draft');
        $susp = $this->store('suspended', 'suspended');
        foreach ([$live, $draft, $susp] as $s) {
            Product::withoutGlobalScopes()->where('store_id', $s->id)->orderBy('id')->first()->update(['name' => 'Unique '.$s->slug, 'sale_price' => 100]);
        }
        $this->get('/')->assertOk()->assertSee('Unique livestore')->assertDontSee('Unique draftstore')->assertDontSee('Unique suspended');
        $this->get('/search?q=Unique')->assertOk()->assertSee('Unique livestore')->assertDontSee('Unique draftstore')->assertDontSee('Unique suspended');
        $this->get('/search?q=nothinghere')->assertOk()->assertSee('কোনো পণ্য পাওয়া যায়নি');
    }

    public function test_hidden_products_are_not_listed_and_search_is_safe(): void
    {
        $live = $this->store('livestore', 'published');
        $p = Product::withoutGlobalScopes()->where('store_id', $live->id)->orderBy('id')->first();
        $p->update(['name' => 'Secret thing', 'is_active' => false]);
        $this->get('/search?q=Secret')->assertOk()->assertDontSee('Secret thing');
        $this->get('/search?q=%25')->assertOk();                       // LIKE wildcard is escaped
        $this->get('/search?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_auth_pages_use_the_new_layout(): void
    {
        $this->get('/login')->assertOk()->assertSee('আপনার দোকান, ব্যবসার জন্য তৈরি');
        $this->get('/register')->assertOk()->assertSee('ফ্রি দোকান খুলুন');
    }
}
