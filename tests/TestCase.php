<?php

namespace Tests;

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
}
