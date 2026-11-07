<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductsTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug): User
    {
        $u = User::factory()->create();
        (new StoreCreator)->create($u, $slug, ucfirst($slug));

        return $u->fresh();
    }

    private function productOf(User $u): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->firstOrFail();
    }

    public function test_one_click_creates_defaults(): void
    {
        $u = $this->seller('alpha');
        $this->assertSame(3, Product::withoutGlobalScopes()->where('store_id', $u->store->id)->count());
    }

    public function test_seller_cannot_touch_other_sellers_product(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $bp = $this->productOf($b);

        $this->actingAs($a)->get(route('seller.products.edit', $bp->id))->assertNotFound();
        $this->actingAs($a)->put(route('seller.products.update', $bp->id), ['name' => 'Hacked', 'price' => 1, 'stock' => 1])->assertNotFound();
        $this->actingAs($a)->delete(route('seller.products.destroy', $bp->id))->assertNotFound();
        $this->assertSame('Sample product 1', Product::withoutGlobalScopes()->find($bp->id)->name);
    }

    public function test_create_product_with_image_and_foreign_category_ignored(): void
    {
        Storage::fake('public');
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $foreignCat = \App\Models\Category::withoutGlobalScopes()->where('store_id', $b->store->id)->first();

        $this->actingAs($a)->post(route('seller.products.store'), [
            'name' => 'Red Shirt', 'price' => 1000, 'sale_price' => 800, 'stock' => 5, 'is_active' => 1,
            'category_id' => $foreignCat->id,
            'images' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ])->assertRedirect();

        $p = Product::withoutGlobalScopes()->where('name', 'Red Shirt')->firstOrFail();
        $this->assertSame($a->store->id, $p->store_id);
        $this->assertNull($p->category_id);
        $img = ProductImage::withoutGlobalScopes()->where('product_id', $p->id)->firstOrFail();
        $this->assertStringEndsWith('.webp', $img->path);
        Storage::disk('public')->assertExists($img->path);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $a = $this->seller('alpha');
        $this->actingAs($a)->post(route('seller.products.store'), ['name' => 'X', 'price' => 100, 'sale_price' => 200, 'stock' => 1])
            ->assertSessionHasErrors('sale_price');
        $this->actingAs($a)->post(route('seller.products.store'), ['name' => 'X', 'price' => 100, 'stock' => 1,
            'images' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])->assertSessionHasErrors('images.0');
    }

    public function test_storefront_only_shows_published_store_and_own_products(): void
    {
        $a = $this->seller('alpha');
        $this->seller('beta');
        $base = config('shop.base_domain');

        $this->get("http://alpha.$base/")->assertNotFound(); // draft
        $a->store->update(['status' => 'published']);
        $this->get("http://alpha.$base/")->assertOk()->assertSee('Sample product 1');
        $this->get("http://beta.$base/")->assertNotFound();  // beta still draft

        $g = $this->seller('gamma');
        $this->productOf($g)->update(['slug' => 'only-in-gamma']);
        $this->get("http://alpha.$base/p/only-in-gamma")->assertNotFound(); // other store's product is invisible
        $this->get("http://alpha.$base/p/sample-product-1")->assertOk();
    }
}
