<?php

namespace Tests\Feature;

use App\Models\DomainCheck;
use App\Models\User;
use App\Services\DomainAvailability;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainCheckTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug): User
    {
        $u = User::factory()->create();
        (new StoreCreator)->create($u, $slug, ucfirst($slug));

        return $u->fresh();
    }

    private function fakeRdap(int $domainStatus): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response([
                'services' => [[['com', 'net'], ['https://rdap.verisign.com/com/v1/']]],
            ]),
            'https://rdap.verisign.com/*' => Http::response(['status' => 'ok'], $domainStatus),
        ]);
    }

    public function test_an_available_domain_is_reported_and_saved_anonymously(): void
    {
        $this->fakeRdap(404);

        $res = $this->post(route('domains.check'), ['domain' => 'TotallyFreeName123.com']);

        $res->assertRedirect();
        $this->assertSame('available', session('domainResult')['status']);
        $check = DomainCheck::firstOrFail();
        $this->assertSame('totallyfreename123.com', $check->domain);
        $this->assertNull($check->user_id);
        $this->assertTrue($check->available);
    }

    public function test_a_taken_domain_is_reported_but_not_saved(): void
    {
        $this->fakeRdap(200);

        $this->post(route('domains.check'), ['domain' => 'google.com']);

        $this->assertSame('taken', session('domainResult')['status']);
        $this->assertSame(0, DomainCheck::count());
    }

    public function test_garbage_input_is_rejected_without_calling_any_api(): void
    {
        Http::fake(); // any call at all fails the test via assertNothingSent below

        $this->post(route('domains.check'), ['domain' => 'not a domain!!']);

        $this->assertSame('invalid', session('domainResult')['status']);
        Http::assertNothingSent();
    }

    public function test_a_logged_in_seller_checking_a_domain_gets_it_saved_under_their_account(): void
    {
        $this->fakeRdap(404);
        $u = $this->seller('alpha');

        $this->actingAs($u)->post(route('domains.check'), ['domain' => 'anotherfreeone.net']);

        $check = DomainCheck::firstOrFail();
        $this->assertSame($u->id, $check->user_id);
        $this->get(route('seller.domains.index'))->assertOk()->assertSee('anotherfreeone.net');
    }

    public function test_a_seller_can_remove_their_own_saved_domain_but_not_someone_elses(): void
    {
        $owner = $this->seller('alpha');
        $other = $this->seller('beta');
        $check = DomainCheck::create(['user_id' => $owner->id, 'domain' => 'mine.com', 'available' => true]);

        $this->actingAs($other)->delete(route('seller.domains.destroy', $check))->assertNotFound();
        $this->assertNotNull($check->fresh());

        $this->actingAs($owner)->delete(route('seller.domains.destroy', $check))->assertRedirect();
        $this->assertNull($check->fresh());
    }

    public function test_admin_can_see_all_domain_leads_including_anonymous_ones(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        DomainCheck::create(['user_id' => null, 'domain' => 'leadone.com', 'available' => true]);

        $this->actingAs($admin)->get(route('admin.domains.index'))->assertOk()->assertSee('leadone.com')->assertSee('Anonymous visitor');
    }

    public function test_an_unsupported_tld_is_reported_as_such_and_not_saved(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => [[['com'], ['https://rdap.verisign.com/com/v1/']]]]),
        ]);

        $this->post(route('domains.check'), ['domain' => 'something.doesnotexisttld']);

        $this->assertSame('unsupported', session('domainResult')['status']);
        $this->assertSame(0, DomainCheck::count());
    }

    public function test_checking_a_name_also_reports_availability_across_common_tlds(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => [
                [['com'], ['https://rdap.verisign.com/com/v1/']],
                [['net'], ['https://rdap.other.example/net/v1/']],
                [['shop'], ['https://rdap.other.example/shop/v1/']],
            ]]),
            'https://rdap.verisign.com/com/v1/domain/nirjhor.com' => Http::response([], 200), // taken
            'https://rdap.other.example/net/v1/domain/nirjhor.net' => Http::response([], 404), // available
            'https://rdap.other.example/shop/v1/domain/nirjhor.shop' => Http::response([], 404), // available
        ]);

        $this->post(route('domains.check'), ['domain' => 'nirjhor.com']);

        $this->assertSame('taken', session('domainResult')['status']);
        $alts = collect(session('domainAlternatives'))->keyBy('domain');
        $this->assertFalse($alts->has('nirjhor.com')); // already shown as the primary result, not duplicated
        $this->assertSame('available', $alts['nirjhor.net']['status']);
        $this->assertSame('available', $alts['nirjhor.shop']['status']);
        $this->assertSame(['nirjhor.net', 'nirjhor.shop'], DomainCheck::orderBy('domain')->pluck('domain')->all());
    }

    public function test_bare_name_without_a_tld_still_checks_common_extensions(): void
    {
        $this->fakeRdap(404);

        $this->post(route('domains.check'), ['domain' => 'BrandNewName']);

        $this->assertSame('invalid', session('domainResult')['status']); // no exact domain was typed
        $alts = collect(session('domainAlternatives'))->pluck('domain');
        $this->assertTrue($alts->contains('brandnewname.com'));
    }

    public function test_the_rdap_bootstrap_lookup_is_cached_across_checks(): void
    {
        $this->fakeRdap(404);

        (new DomainAvailability)->check('one.com');
        (new DomainAvailability)->check('two.com');

        Http::assertSentCount(3); // 1 bootstrap fetch + 2 domain lookups, not 2+2
        $this->assertTrue(Cache::has('rdap-bootstrap-map'));
    }
}
