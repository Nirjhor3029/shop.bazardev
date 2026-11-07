<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Services\DemoStore;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_gives_a_filled_non_admin_seller(): void
    {
        config(['app.demo_mode' => true]);
        $this->post(route('demo.login'))->assertRedirect(route('seller.dashboard'));
        $this->assertAuthenticated();
        $u = auth()->user();
        $this->assertFalse((bool) $u->is_admin);
        $this->assertSame(6, \App\Models\Product::withoutGlobalScopes()->where('store_id', $u->store->id)->count());
        $this->assertSame('published', $u->store->status);
    }

    public function test_demo_login_is_off_when_demo_mode_off(): void
    {
        config(['app.demo_mode' => false]);
        $this->post(route('demo.login'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_demo_session_cannot_change_account_or_unpublish(): void
    {
        config(['app.demo_mode' => true]);
        $this->post(route('demo.login'));
        $before = auth()->user()->email;
        $this->patch(route('profile.update'), ['name' => 'Hacker', 'email' => 'hacker@evil.test'])->assertRedirect();
        $this->delete(route('profile.destroy'), ['password' => 'x'])->assertRedirect();
        $this->post(route('seller.publish'), ['publish' => 0])->assertRedirect();
        $u = auth()->user()->fresh();
        $this->assertSame($before, $u->email);
        $this->assertSame('published', $u->store->status);
    }

    public function test_demo_reset_rebuilds_store(): void
    {
        config(['app.demo_mode' => true]);
        $first = DemoStore::ensure()->store->id;
        DemoStore::reset();
        $u = DemoStore::user()->fresh();
        $this->assertNotSame($first, $u->store->id);
        $this->assertSame(1, Store::where('slug', 'demo')->count());
    }

    public function test_admin_area_is_hidden_from_normal_users_and_works_for_admin(): void
    {
        $u = User::factory()->create();
        (new StoreCreator)->create($u, 'alpha', 'Alpha');
        $this->actingAs($u)->get(route('admin.index'))->assertNotFound();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('alpha');
    }

    public function test_suspended_store_is_offline_and_owner_cannot_republish(): void
    {
        $owner = User::factory()->create();
        $store = (new StoreCreator)->create($owner, 'alpha', 'Alpha');
        $store->update(['status' => 'published']);
        $base = config('shop.base_domain');
        $this->get("http://alpha.$base/")->assertOk();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.stores.status', $store), ['status' => 'suspended'])->assertRedirect();
        $this->get("http://alpha.$base/")->assertNotFound();

        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');
        $this->actingAs($owner)->post(route('seller.publish'), ['publish' => 1])->assertForbidden();
        $this->assertSame('suspended', $store->fresh()->status);

        $this->app['auth']->forgetGuards();
        $this->actingAs($owner)->post(route('admin.stores.status', $store), ['status' => 'published'])->assertNotFound(); // owner is not admin
    }
}
