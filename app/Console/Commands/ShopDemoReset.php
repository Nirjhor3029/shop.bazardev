<?php

namespace App\Console\Commands;

use App\Services\DemoStore;
use Illuminate\Console\Command;

class ShopDemoReset extends Command
{
    protected $signature = 'shop:demo-reset';
    protected $description = 'Rebuild the public demo store from scratch (demo seller only)';

    public function handle(): int
    {
        if (! config('app.demo_mode')) {
            $this->error('DEMO_MODE is off.');

            return self::FAILURE;
        }
        DemoStore::reset();
        $this->info('Demo store rebuilt.');

        return self::SUCCESS;
    }
}
