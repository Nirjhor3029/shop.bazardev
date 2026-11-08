<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    private function save(User $u, array $over = [])
    {
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        return $this->actingAs($u)->post(route('seller.appearance.update'), array_merge([
            'theme' => 'bold', 'color' => '#ff0066', 'font' => 'serif',
            'sections' => ['hero' => ['order' => 20, 'on' => 1], 'categories' => ['order' => 30], 'products' => ['order' => 10, 'on' => 1], 'about' => ['order' => 40, 'on' => 1]],
        ], $over));
    }

    public function test_page_loads_and_settings_are_saved(): void
    {
        $a = $this->seller();
        $this->actingAs($a)->get(route('seller.appearance.edit'))->assertOk()->assertSee('Theme');
        $this->save($a, ['announcement' => 'Sale today', 'hero_title' => 'Welcome', 'about' => 'We sell things', 'phone' => '0171'])->assertSessionHasNoErrors();
        $s = $a->store->fresh();
        $this->assertSame('bold', $s->theme);
        $this->assertSame('#ff0066', $s->settings['color']);
        $this->assertSame('Welcome', $s->look()['hero_title']);
        $this->assertSame(['products', 'hero', 'about'], $s->sections()); // ordered by the numbers, categories is off
        $this->assertSame(60.0, (float) $s->settings['delivery_charge']); // other settings untouched
    }

    public function test_validation(): void
    {
        $a = $this->seller();
        $this->save($a, ['theme' => 'hacker'])->assertSessionHasErrors('theme');
        $this->save($a, ['color' => 'red; background:url(x)'])->assertSessionHasErrors('color');
        $this->save($a, ['facebook' => 'javascript:alert(1)'])->assertSessionHasErrors('facebook');
        $this->save($a, ['facebook' => 'http://insecure.test'])->assertSessionHasErrors('facebook');
        $this->save($a, ['logo' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('logo');
    }

    public function test_logo_and_banner_become_webp_and_old_files_are_removed(): void
    {
        Storage::fake('public');
        $a = $this->seller();
        $this->save($a, ['logo' => UploadedFile::fake()->image('l.png', 600, 300), 'banner' => UploadedFile::fake()->image('b.jpg', 2400, 800)])->assertSessionHasNoErrors();
        $look = $a->store->fresh()->look();
        $this->assertStringEndsWith('.webp', $look['logo']);
        Storage::disk('public')->assertExists($look['logo']);
        Storage::disk('public')->assertExists($look['banner']);
        [$w] = getimagesizefromstring(Storage::disk('public')->get($look['banner']));
        $this->assertLessThanOrEqual(1600, $w);

        $this->save($a, ['logo' => UploadedFile::fake()->image('l2.png', 100, 100)]);
        Storage::disk('public')->assertMissing($look['logo']);
        $this->save($a, ['remove_banner' => 1]);
        Storage::disk('public')->assertMissing($look['banner']);
        $this->assertNull($a->store->fresh()->look()['banner']);
    }

    public function test_storefront_shows_theme_text_and_escapes_html(): void
    {
        $a = $this->seller();
        $this->save($a, ['theme' => 'boutique', 'hero_title' => '<script>alert(1)</script>Hi', 'announcement' => 'Offer <b>now</b>']);
        app()->forgetInstance('currentStore');
        $res = $this->get('http://alpha.'.config('shop.base_domain').'/')->assertOk();
        $res->assertSee('theme-boutique', false)->assertSee('font-serif-x', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $res->assertSee('Offer &lt;b&gt;now&lt;/b&gt;', false);
    }

    public function test_vibrant_and_mono_themes_render_with_overlay_choice(): void
    {
        $a = $this->seller();
        $this->save($a, ['theme' => 'vibrant', 'overlay' => 'soft'])->assertSessionHasNoErrors();
        $this->assertSame('soft', $a->store->fresh()->look()['overlay']);
        app()->forgetInstance('currentStore');
        $this->get('http://alpha.'.config('shop.base_domain').'/')->assertOk()->assertSee('theme-vibrant', false)->assertSee('sf-hero-vibrant', false);

        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->save($a, ['theme' => 'mono', 'overlay' => 'none'])->assertSessionHasNoErrors();
        app()->forgetInstance('currentStore');
        $this->get('http://alpha.'.config('shop.base_domain').'/')->assertOk()->assertSee('theme-mono', false)->assertSee('sf-hero-mono-text', false);
    }

    public function test_category_filter(): void
    {
        $a = $this->seller();
        app()->instance('currentStore', $a->store);
        $c = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
        Product::create(['category_id' => $c->id, 'name' => 'Red Shoe', 'slug' => 'red-shoe', 'price' => 10, 'stock' => 5]);
        app()->forgetInstance('currentStore');
        $base = 'http://alpha.'.config('shop.base_domain');
        $this->get("$base/?c=shoes")->assertOk()->assertSee('Red Shoe')->assertDontSee('Sample product 1');
        $this->get("$base/?c=nonsense")->assertOk()->assertSee('Sample product 1');
    }

    public function test_other_seller_cannot_change_my_store(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $this->save($b, ['theme' => 'bold', 'hero_title' => 'B was here']);
        $this->assertSame('minimal', $a->store->fresh()->theme);
        $this->assertSame('', $a->store->fresh()->look()['hero_title']);
    }
}
