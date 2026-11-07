<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoStore
{
    public const EMAIL = 'demo-seller@shop.bazardev.com';
    public const SLUG = 'demo';

    public static function user(): User
    {
        $u = User::firstOrCreate(['email' => self::EMAIL], ['name' => 'Demo Seller', 'password' => bcrypt(Str::random(40))]);
        $u->forceFill(['email_verified_at' => $u->email_verified_at ?? now(), 'is_admin' => false])->save();

        return $u;
    }

    /** Make sure the demo seller has a published, filled demo store. */
    public static function ensure(): User
    {
        $u = self::user();
        if (! $u->store) {
            self::build($u);
            $u->load('store');
        }

        return $u;
    }

    /** Delete the demo store (its products, orders, images) and build it again. */
    public static function reset(): void
    {
        $u = self::user();
        if ($old = $u->store) {
            Storage::disk('public')->deleteDirectory('products/'.$old->id);
            $old->delete(); // cascades to products, orders, payment methods
        }
        self::build($u);
    }

    private static function build(User $u): void
    {
        DB::transaction(function () use ($u) {
            $store = (new StoreCreator)->create($u, self::SLUG, 'Demo Fashion House');
            $store->update(['status' => 'published', 'theme' => 'boutique', 'settings' => array_merge($store->settings, ['color' => '#be185d', 'delivery_charge' => 60, 'free_delivery_over' => 2000, 'look' => ['announcement' => 'Demo store: free delivery over 2000', 'hero_title' => 'Eid Collection', 'hero_text' => 'Panjabi, saree and more, ready to ship.', 'hero_button' => 'Shop now', 'about' => 'This is a demo shop built in one click.', 'phone' => '01700000000']])]);

            $prev = app()->bound('currentStore') ? app('currentStore') : null;
            app()->instance('currentStore', $store);
            try {
                Product::where('is_sample', true)->each(fn ($p) => $p->delete());
                Category::query()->delete();
                $cats = collect(['Men', 'Women', 'Accessories'])->mapWithKeys(fn ($n) => [$n => Category::create(['name' => $n, 'slug' => Str::slug($n)])]);
                $items = [
                    ['Cotton Panjabi', 'Men', 1800, 1500, 25, [190, 24, 93]],
                    ['Slim Fit Shirt', 'Men', 1200, null, 40, [30, 64, 175]],
                    ['Printed Saree', 'Women', 3200, 2700, 12, [180, 83, 9]],
                    ['Kurti Set', 'Women', 1500, null, 30, [5, 150, 105]],
                    ['Leather Wallet', 'Accessories', 650, 550, 60, [87, 83, 78]],
                    ['Canvas Bag', 'Accessories', 900, null, 0, [124, 58, 237]],
                ];
                foreach ($items as $i => [$name, $cat, $price, $sale, $stock, $rgb]) {
                    $p = Product::create([
                        'category_id' => $cats[$cat]->id, 'name' => $name, 'slug' => Str::slug($name),
                        'description' => "A demo product for the ".$name.". Edit it, hide it or delete it from the Products page.",
                        'price' => $price, 'sale_price' => $sale, 'stock' => $stock, 'is_sample' => false,
                    ]);
                    $path = "products/{$store->id}/demo-{$i}.webp";
                    Storage::disk('public')->put($path, self::placeholder($rgb));
                    ProductImage::create(['product_id' => $p->id, 'path' => $path, 'sort_order' => 1]);
                }
                PaymentMethod::updateOrCreate(['type' => 'bkash'], ['number' => '01700000000', 'instructions' => 'Demo number. Do not send real money.', 'is_active' => true]);
            } finally {
                app()->instance('currentStore', $prev);
            }
        });
    }

    private static function placeholder(array $rgb): string
    {
        $im = imagecreatetruecolor(800, 800);
        for ($y = 0; $y < 800; $y++) {
            $f = 0.55 + 0.45 * ($y / 800);
            imageline($im, 0, $y, 800, $y, imagecolorallocate($im, (int) ($rgb[0] * $f), (int) ($rgb[1] * $f), (int) ($rgb[2] * $f)));
        }
        ob_start();
        imagewebp($im, null, 80);

        return ob_get_clean();
    }
}
