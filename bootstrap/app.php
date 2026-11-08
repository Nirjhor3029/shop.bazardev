<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller.store' => \App\Http\Middleware\SetSellerStore::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'store.host' => \App\Http\Middleware\ResolveStoreFromHost::class,
        ]);
        // SSLCommerz posts these redirects/webhooks itself - it carries no Laravel CSRF token.
        $middleware->validateCsrfTokens(except: ['sslcommerz/*']);
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, \App\Http\Middleware\ProtectDemoSession::class]);
        // The tenant must be known BEFORE route-model binding runs, or scoped lookups see no store.
        $middleware->prependToPriorityList(before: \Illuminate\Routing\Middleware\SubstituteBindings::class, prepend: \App\Http\Middleware\SetSellerStore::class);
        $middleware->prependToPriorityList(before: \Illuminate\Routing\Middleware\SubstituteBindings::class, prepend: \App\Http\Middleware\ResolveStoreFromHost::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
