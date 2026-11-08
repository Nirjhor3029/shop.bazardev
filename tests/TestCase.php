<?php

namespace Tests;

use App\Models\Plan;
use App\Models\Store;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety: never let tests touch the real database (a cached config can silently ignore phpunit.xml).
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->fail('Refusing to run: tests must use sqlite :memory:. Run `php artisan config:clear` first.');
        }
    }

    /**
     * Most feature tests create a store on the default (Free) plan; use this when a test
     * exercises a paid-plan-only feature. If you call this mid-test on a User/Store you've
     * already used with actingAs()/accessed ->store on, also call $user->refresh() before the
     * next actingAs($user) - actingAs() reuses that exact object, including its now-stale
     * cached "store" relation, so the plan change won't be visible until it's refreshed.
     */
    protected function upgradeToPro(Store $store): Store
    {
        $store->update(['plan_id' => Plan::where('slug', 'pro')->firstOrFail()->id]);

        return $store->fresh();
    }
}
