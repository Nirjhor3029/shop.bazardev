<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ProtectDemoSession;
use App\Http\Middleware\ResolveStoreFromCustomDomain;
use App\Http\Middleware\ResolveStoreFromHost;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetSellerStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller.store' => SetSellerStore::class,
            'admin' => EnsureAdmin::class,
            'store.host' => ResolveStoreFromHost::class,
            'store.custom-domain' => ResolveStoreFromCustomDomain::class,
        ]);
        // SSLCommerz posts these redirects/webhooks itself - it carries no Laravel CSRF token.
        $middleware->validateCsrfTokens(except: ['sslcommerz/*']);
        $middleware->web(append: [SetLocale::class, ProtectDemoSession::class]);
        // The tenant must be known BEFORE route-model binding runs, or scoped lookups see no store.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetSellerStore::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolveStoreFromHost::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolveStoreFromCustomDomain::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
