<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StoreCreator
{
    /** ONE CLICK: store + defaults + sample data, all in one transaction. */
    public function create(User $user, string $slug, string $name): Store
    {
        return DB::transaction(function () use ($user, $slug, $name) {
            $store = Store::create([
                'user_id' => $user->id, 'slug' => $slug, 'name' => $name,
                'status' => 'draft', 'theme' => 'minimal',
                'settings' => ['color' => '#4f46e5', 'currency' => 'BDT', 'delivery_charge' => 60],
            ]);

            $previous = app()->bound('currentStore') ? app('currentStore') : null;
            app()->instance('currentStore', $store);
            try {
                PaymentMethod::create(['type' => 'cod', 'is_active' => true, 'instructions' => 'Pay in cash when you receive the order.']);
                $cat = Category::create(['name' => 'General', 'slug' => 'general']);
                foreach ([['Sample product 1', 500], ['Sample product 2', 850], ['Sample product 3', 1200]] as $i => [$n, $price]) {
                    Product::create([
                        'category_id' => $cat->id, 'name' => $n, 'slug' => 'sample-product-'.($i + 1),
                        'description' => 'This is a sample product. Edit or delete it from your dashboard.',
                        'price' => $price, 'stock' => 10, 'is_sample' => true,
                    ]);
                }
            } finally {
                app()->instance('currentStore', $previous);
            }

            return $store;
        });
    }
}
