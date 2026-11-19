<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\StoreDomain;
use App\Models\User;
use App\Services\DomainVerifier;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    public function test_free_plan_cannot_add_a_custom_domain(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com'])
            ->assertStatus(403);
        $this->assertSame(0, StoreDomain::count());
    }

    public function test_pro_plan_can_add_a_domain_and_gets_a_verification_token(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'MyShop.com'])
            ->assertSessionHasNoErrors();

        $domain = StoreDomain::firstOrFail();
        $this->assertSame('myshop.com', $domain->domain); // lowercased
        $this->assertSame('pending', $domain->status);
        $this->assertSame($u->store->id, $domain->store_id);
        $this->assertNotEmpty($domain->verification_token);
    }

    public function test_cannot_add_a_second_domain_without_removing_the_first(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com'])->assertSessionHasNoErrors();
        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'another.com'])
            ->assertSessionHasErrors('domain');
        $this->assertSame(1, StoreDomain::count());
    }

    public function test_cannot_claim_the_platforms_own_domain_or_a_subdomain_of_it(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => config('shop.base_domain')])
            ->assertSessionHasErrors('domain');
        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'evil.'.config('shop.base_domain')])
            ->assertSessionHasErrors('domain');
        $this->assertSame(0, StoreDomain::count());
    }

    public function test_two_stores_cannot_claim_the_same_domain(): void
    {
        $a = $this->seller('alpha');
        $this->upgradeToPro($a->store);
        $a->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com'])->assertSessionHasNoErrors();

        $b = $this->seller('beta');
        $this->upgradeToPro($b->store);
        $b->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($b)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com'])
            ->assertSessionHasErrors('domain');

        $this->assertSame(1, StoreDomain::count());
    }

    public function test_seller_cannot_recheck_or_remove_another_stores_domain(): void
    {
        $a = $this->seller('alpha');
        $this->upgradeToPro($a->store);
        $a->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($a)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com']);
        $domain = StoreDomain::firstOrFail();

        $b = $this->seller('beta');
        $this->upgradeToPro($b->store);
        $b->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->actingAs($b)->post(route('seller.custom-domain.recheck', $domain))->assertNotFound();
        $this->actingAs($b)->delete(route('seller.custom-domain.destroy', $domain))->assertNotFound();
        $this->assertSame(1, StoreDomain::count());
    }

    public function test_index_page_renders_in_every_state(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->get(route('seller.custom-domain.index'))->assertOk()->assertSee('Pro plan feature');

        $this->upgradeToPro($u->store);
        $u->refresh();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($u)->get(route('seller.custom-domain.index'))->assertOk()->assertSee('Add domain');

        $this->actingAs($u)->post(route('seller.custom-domain.store'), ['domain' => 'myshop.com'])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($u)->get(route('seller.custom-domain.index'))->assertOk()->assertSee('Pending');

        StoreDomain::firstOrFail()->update(['status' => 'verified', 'verified_at' => now()]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($u)->get(route('seller.custom-domain.index'))->assertOk()->assertSee('Verified');
    }

    public function test_domain_verifier_matches_only_the_correct_txt_token(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $domain = $u->store->fresh()->domains()->create([
            'domain' => 'this-domain-definitely-does-not-exist-'.uniqid().'.test',
            'verification_token' => 'some-token',
            'status' => 'pending',
        ]);

        $verified = (new DomainVerifier)->check($domain);

        $this->assertFalse($verified);
        $this->assertSame('failed', $domain->fresh()->status);
        $this->assertNotNull($domain->fresh()->last_checked_at);
    }

    // --- M2: routing. The new catch-all `{customDomain}` domain group must never shadow the
    // base domain or its subdomains, whichever order routes happen to be tried in - verified
    // here by actually requesting different Host values, not just reading the regex. ---

    public function test_base_domain_and_subdomain_routing_are_unaffected_by_the_new_catch_all(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);

        $this->get('http://'.config('shop.base_domain').'/')->assertOk(); // marketplace home, not a storefront
        $this->get('http://alpha.'.config('shop.base_domain').'/')->assertOk();
        $this->get('http://'.config('shop.base_domain').'/alpha')->assertOk();
    }

    public function test_an_unverified_custom_domain_404s(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->store->fresh()->domains()->create([
            'domain' => 'myshop.com', 'verification_token' => 'x', 'status' => 'pending',
        ]);

        $this->get('http://myshop.com/')->assertNotFound();
    }

    public function test_a_domain_nobody_ever_claimed_404s(): void
    {
        $this->get('http://nobody-owns-this-one.com/')->assertNotFound();
    }

    public function test_a_verified_custom_domain_serves_that_stores_home_and_product_page(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->store->fresh()->domains()->create([
            'domain' => 'myshop.com', 'verification_token' => 'x', 'status' => 'verified', 'verified_at' => now(),
        ]);

        $this->get('http://myshop.com/')->assertOk();
        $this->get('http://myshop.com/p/sample-product-1')->assertOk();
    }

    public function test_a_verified_domain_stops_working_the_moment_its_store_loses_the_pro_plan(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->store->fresh()->domains()->create([
            'domain' => 'myshop.com', 'verification_token' => 'x', 'status' => 'verified', 'verified_at' => now(),
        ]);
        $this->get('http://myshop.com/')->assertOk();

        $u->store->update(['plan_id' => Plan::where('slug', 'free')->firstOrFail()->id]);
        $this->get('http://myshop.com/')->assertNotFound();
    }

    public function test_internal_domain_check_endpoint_recognizes_a_verified_custom_domain(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $u->store->fresh()->domains()->create([
            'domain' => 'myshop.com', 'verification_token' => 'x', 'status' => 'verified', 'verified_at' => now(),
        ]);

        $this->get('/internal/domain-check?domain=myshop.com')->assertOk();
        $this->get('/internal/domain-check?domain=not-claimed-by-anyone.com')->assertNotFound();
    }
}
