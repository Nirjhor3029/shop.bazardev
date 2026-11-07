<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Public demo store is rebuilt every hour (needs the cron for `schedule:run`).
\Illuminate\Support\Facades\Schedule::command('shop:demo-reset')->hourly()->when(fn () => config('app.demo_mode'));
