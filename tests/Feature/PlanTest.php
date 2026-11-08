<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    private function url(User $u, string $path): string
    {
        return $u->store->fresh()->url().$path;
    }

    public function test_new_signup_gets_the_default_free_plan(): void
    {
        $u = $this->seller();
        $this->assertSame('free', $u->store->activePlan()->slug);
        $this->assertFalse($u->store->activePlan()->allow_subdomain);
    }

    public function test_free_plan_stores_internal_links_actually_point_inside_the_store_path(): void
    {
        // Regression: every internal href/action used to be root-relative ("/p/x", "/cart"),
        // which is correct for a subdomain store but 404s for a path-based (Free plan) one,
        // since those live under /{slug}/... not the domain root. Follow the real rendered
        // links rather than constructing URLs by hand, so this would have caught that bug.
        $u = $this->seller();
        $home = $this->get($this->url($u, '/'))->assertOk();

        preg_match('/href="([^"]*\/p\/sample-product-1)"/', $home->getContent(), $m);
        $this->assertNotEmpty($m, 'product card link not found on the home page');
        $this->get($m[1])->assertOk();

        preg_match('/href="([^"]*\/cart)"/', $home->getContent(), $m);
        $this->get($m[1])->assertOk();

        $product = $this->get($this->url($u, '/p/sample-product-1'))->assertOk();
        preg_match('/action="([^"]*\/cart\/add)"/', $product->getContent(), $m);
        $this->assertNotEmpty($m, 'add-to-cart form action not found on the product page');
        $this->post($m[1], ['product_id' => Product::withoutGlobalScopes()->where('slug', 'sample-product-1')->first()->id])
            ->assertRedirect($this->url($u, '/cart'));
    }

    public function test_free_plan_store_is_reachable_by_path_but_not_subdomain(): void
    {
        $u = $this->seller();
        $this->assertSame('https://'.config('shop.base_domain').'/alpha', $u->store->fresh()->url());

        $this->get($this->url($u, '/'))->assertOk();
        $this->get('http://alpha.'.config('shop.base_domain').'/')->assertNotFound();
    }

    public function test_pro_plan_store_gets_a_subdomain(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $this->assertSame('https://alpha.'.config('shop.base_domain'), $u->store->fresh()->url());

        $this->get('http://alpha.'.config('shop.base_domain').'/')->assertOk();
        $this->get($this->url($u, '/'))->assertOk(); // the path URL still works too
    }

    public function test_expired_plan_falls_back_to_free(): void
    {
        $u = $this->seller();
        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $u->store->update(['plan_id' => $pro->id, 'plan_expires_at' => now()->subDay()]);
        $this->assertSame('free', $u->store->fresh()->activePlan()->slug);

        $u->store->update(['plan_expires_at' => now()->addDay()]);
        $this->assertSame('pro', $u->store->fresh()->activePlan()->slug);
    }

    public function test_free_plan_blocks_products_beyond_its_limit(): void
    {
        $u = $this->seller();
        $u->store->update(['plan_id' => Plan::where('slug', 'free')->firstOrFail()->id]);
        Plan::where('slug', 'free')->update(['max_products' => 3]); // already has 3 sample products

        $this->actingAs($u)->post(route('seller.products.store'), ['name' => 'Extra', 'price' => 100, 'stock' => 1])
            ->assertSessionHasErrors('limit');
        $this->assertSame(3, Product::count());
    }

    public function test_free_plan_blocks_a_second_landing_page_and_premium_templates(): void
    {
        $u = $this->seller();
        $p = Product::withoutGlobalScopes()->where('store_id', $u->store->id)->firstOrFail();

        $this->actingAs($u)->post(route('seller.landing.store'), ['product_id' => $p->id, 'template' => 'combo', 'is_published' => '1'])
            ->assertSessionHasErrors('template');
        $this->assertSame(0, LandingPage::count());

        $this->actingAs($u)->post(route('seller.landing.store'), ['product_id' => $p->id, 'template' => 'classic', 'is_published' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, LandingPage::count());

        $this->actingAs($u)->post(route('seller.landing.store'), ['product_id' => $p->id, 'template' => 'classic', 'is_published' => '1'])
            ->assertSessionHasErrors('limit');
        $this->assertSame(1, LandingPage::count());
    }

    public function test_free_plan_blocks_pixel_tracking_on_a_landing_page(): void
    {
        $u = $this->seller();
        $p = Product::withoutGlobalScopes()->where('store_id', $u->store->id)->firstOrFail();

        $this->actingAs($u)->post(route('seller.landing.store'), [
            'product_id' => $p->id, 'template' => 'classic', 'is_published' => '1', 'pixel_id' => '1234567890',
        ])->assertSessionHasErrors('pixel_id');
    }

    public function test_free_plan_blocks_premium_themes(): void
    {
        $u = $this->seller();
        $sections = ['hero' => ['order' => 10, 'on' => 1]];
        $this->actingAs($u)->post(route('seller.appearance.update'), [
            'theme' => 'bold', 'color' => '#ff0066', 'font' => 'sans', 'sections' => $sections,
        ])->assertSessionHasErrors('theme');
        $this->assertSame('minimal', $u->store->fresh()->theme);

        $this->actingAs($u)->post(route('seller.appearance.update'), [
            'theme' => 'boutique', 'color' => '#ff0066', 'font' => 'sans', 'sections' => $sections,
        ])->assertSessionHasNoErrors();
        $this->assertSame('boutique', $u->store->fresh()->theme);
    }

    public function test_free_plan_blocks_premium_product_templates_pro_plan_allows_them(): void
    {
        $u = $this->seller();
        $sections = ['hero' => ['order' => 10, 'on' => 1]];
        $this->actingAs($u)->post(route('seller.appearance.update'), [
            'theme' => 'minimal', 'product_template' => 'daraz', 'color' => '#ff0066', 'font' => 'sans', 'sections' => $sections,
        ])->assertSessionHasErrors('product_template');
        $this->assertSame('classic', $u->store->fresh()->product_template);

        $this->upgradeToPro($u->store);
        $u->refresh(); // $u's own cached "store" relation is now stale - actingAs() reuses this exact object
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($u)->post(route('seller.appearance.update'), [
            'theme' => 'minimal', 'product_template' => 'alibaba', 'color' => '#ff0066', 'font' => 'sans', 'sections' => $sections,
        ])->assertSessionHasNoErrors();
        $this->assertSame('alibaba', $u->store->fresh()->product_template);
    }

    public function test_each_product_template_renders_with_and_without_variants(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $p = Product::withoutGlobalScopes()->where('store_id', $u->store->id)->firstOrFail();
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue', 'generate_variants' => '1',
        ]);
        $withVariants = Product::withoutGlobalScopes()->where('id', $p->id)->firstOrFail();
        $plain = Product::withoutGlobalScopes()->where('store_id', $u->store->id)->where('id', '!=', $p->id)->firstOrFail();

        foreach (array_keys(Store::PRODUCT_TEMPLATES) as $template) {
            $this->actingAs($u)->post(route('seller.appearance.update'), [
                'theme' => 'minimal', 'product_template' => $template, 'color' => '#ff0066', 'font' => 'sans',
                'sections' => ['hero' => ['order' => 10, 'on' => 1]],
            ])->assertSessionHasNoErrors();
            $this->app['auth']->forgetGuards();
            app()->forgetInstance('currentStore');

            $this->get($this->url($u, '/p/'.$withVariants->slug))->assertOk()->assertSee('Color');
            $this->get($this->url($u, '/p/'.$plain->slug))->assertOk();

            $this->actingAs($u);
            app()->forgetInstance('currentStore');
        }
    }

    public function test_free_plan_cannot_activate_sslcommerz_even_with_credentials(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->put(route('seller.settings.update'), [
            'name' => 'Alpha', 'delivery_charge' => 60,
            'sslcommerz_on' => '1', 'sslcommerz_store_id' => 'sid', 'sslcommerz_store_passwd' => 'pass',
        ])->assertSessionHasNoErrors();

        $this->assertFalse(PaymentMethod::where('type', 'sslcommerz')->first()->is_active);
    }

    public function test_free_plan_keeps_the_made_with_footer_pro_plan_removes_it(): void
    {
        $u = $this->seller();
        $this->get($this->url($u, '/'))->assertSee('বাজারদেব শপে তৈরি');

        $this->upgradeToPro($u->store);
        $this->get($this->url($u, '/'))->assertDontSee('বাজারদেব শপে তৈরি');
    }

    public function test_admin_can_create_a_plan_and_it_becomes_the_sole_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_default' => '1', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $starter = Plan::where('slug', 'starter')->firstOrFail();
        $this->assertTrue($starter->is_default);
        $this->assertFalse(Plan::where('slug', 'free')->firstOrFail()->is_default);
        $this->assertSame(1, Plan::where('is_default', true)->count());
    }

    public function test_admin_cannot_delete_the_default_plan_or_a_plan_with_stores_on_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $free = Plan::where('slug', 'free')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.plans.destroy', $free))->assertStatus(422); // it's the default

        $u = $this->seller();
        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $u->store->update(['plan_id' => $pro->id]);
        $this->actingAs($admin)->delete(route('admin.plans.destroy', $pro))->assertStatus(422); // a store is on it

        $u->store->update(['plan_id' => $free->id]);
        $this->actingAs($admin)->delete(route('admin.plans.destroy', $pro))->assertRedirect(route('admin.plans.index'));
        $this->assertNull(Plan::where('slug', 'pro')->first());
    }

    public function test_admin_can_assign_a_plan_to_a_store(): void
    {
        $u = $this->seller();
        $admin = User::factory()->create(['is_admin' => true]);
        $pro = Plan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.stores.plan', $u->store), [
            'plan_id' => $pro->id, 'plan_expires_at' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('pro', $u->store->fresh()->activePlan()->slug);
    }
}
