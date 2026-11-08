<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Seller\AppearanceController;
use App\Http\Controllers\Seller\CategoryController;
use App\Http\Controllers\Seller\LandingPageController as SellerLandingPageController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\SettingsController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\StorefrontController;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// STOREFRONT: STORE.shop.bazardev.com
Route::domain('{store}.'.config('shop.base_domain'))
    ->middleware('store.host')
    ->where(['store' => '[a-z0-9-]{3,30}'])
    ->group(function () {
        Route::get('/', [StorefrontController::class, 'home']);
        Route::get('/p/{slug}', [StorefrontController::class, 'product'])->where('slug', '[a-z0-9-]+');
        Route::get('/lp/{slug}', [LandingPageController::class, 'show'])->where('slug', '[a-z0-9-]+');
        Route::post('/lp/{slug}/order', [LandingPageController::class, 'order'])->where('slug', '[a-z0-9-]+')->middleware('throttle:10,1');
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/add', [CartController::class, 'add'])->middleware('throttle:30,1');
        Route::post('/cart/update', [CartController::class, 'update'])->middleware('throttle:60,1');
        Route::get('/checkout', [CheckoutController::class, 'show']);
        Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:10,1');
        Route::get('/order/{token}', [CheckoutController::class, 'order'])->where('token', '[A-Za-z0-9]{40}');
    });

// MAIN DOMAIN: shop.bazardev.com
Route::get('/', [MarketController::class, 'home'])->name('home');
Route::get('/search', [MarketController::class, 'search'])->middleware('throttle:60,1')->name('search');
Route::get('/lang/{locale}', [MarketController::class, 'lang'])->name('lang');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('seller.dashboard'))->name('dashboard');
    Route::get('/store/create', [SellerController::class, 'create'])->name('store.create');
    Route::post('/store/create', [SellerController::class, 'store'])->name('store.store');
    Route::get('/store/check', [SellerController::class, 'check'])->middleware('throttle:60,1')->name('store.check');

    Route::middleware('seller.store')->prefix('seller')->group(function () {
        Route::get('/', [SellerController::class, 'dashboard'])->name('seller.dashboard');
        Route::post('/publish', [SellerController::class, 'publish'])->name('seller.publish');
        Route::resource('products', ProductController::class)->except('show')->names('seller.products');
        Route::get('orders', [OrderController::class, 'index'])->name('seller.orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('seller.orders.show');
        Route::post('orders/{order}/status', [OrderController::class, 'status'])->name('seller.orders.status');
        Route::post('orders/{order}/payment', [OrderController::class, 'payment'])->name('seller.orders.payment');
        Route::get('appearance', [AppearanceController::class, 'edit'])->name('seller.appearance.edit');
        Route::post('appearance', [AppearanceController::class, 'update'])->name('seller.appearance.update');
        Route::resource('landing', SellerLandingPageController::class)->except('show')->names('seller.landing');
        Route::get('settings', [SettingsController::class, 'edit'])->name('seller.settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('seller.settings.update');
        Route::get('categories', [CategoryController::class, 'index'])->name('seller.categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('seller.categories.store');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('seller.categories.destroy');
    });
});

Route::post('/demo-login', DemoLoginController::class)->middleware('throttle:20,1')->name('demo.login');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/stores/{store}/status', [AdminController::class, 'status'])->name('admin.stores.status');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Caddy on-demand TLS check (local only), port 8098.
Route::get('/internal/domain-check', function (Request $request) {
    abort_unless(in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
    $domain = strtolower((string) $request->query('domain'));
    $base = config('shop.base_domain');
    if ($domain === $base) {
        return response('ok');
    }
    if (preg_match('/^([a-z0-9-]{3,30})\.'.preg_quote($base, '/').'$/', $domain, $m)
        && Store::where('slug', $m[1])->where('status', 'published')->exists()) {
        return response('ok');
    }
    abort(404);
});
