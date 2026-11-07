<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Storefront views always get the brand color and currency of the current store.
        \Illuminate\Support\Facades\View::composer('storefront.*', function ($view) {
            $store = app()->bound('currentStore') ? app('currentStore') : null;
            $view->with('color', $store?->settings['color'] ?? '#4f46e5')->with('cur', $store?->settings['currency'] ?? 'BDT')->with('look', $store?->look() ?? []);
        });
        //
    }
}
