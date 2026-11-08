<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderPlacer;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);
        $this->upgradeToPro($s);

        return $u->fresh();
    }

    private function product(User $u): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->orderBy('id')->firstOrFail(); // price 500, stock 10
    }

    private function url(User $u, string $path): string
    {
        return $u->store->fresh()->url().$path;
    }

    public function test_generating_variants_builds_the_full_combination_matrix(): void
    {
        $u = $this->seller();
        $p = $this->product($u);

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue',
            'option2_name' => 'Size', 'option2_values' => 'S, M, L',
            'generate_variants' => '1',
        ])->assertSessionHasNoErrors();

        $variants = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->get();
        $this->assertCount(6, $variants); // 2 colors x 3 sizes
        $this->assertTrue($variants->contains(fn ($v) => $v->options === ['Color' => 'Red', 'Size' => 'S']));
    }

    public function test_regenerating_keeps_price_and_stock_of_combos_that_still_exist(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue', 'generate_variants' => '1',
        ]);
        $red = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->where('options', json_encode(['Color' => 'Red']))->firstOrFail();
        $red->update(['price' => 777, 'stock' => 42]);

        // regenerate with Red+Green instead of Red+Blue - Red should survive with its price/stock, Blue should be gone, Green is new
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Green', 'generate_variants' => '1',
        ]);

        $variants = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->get();
        $this->assertCount(2, $variants);
        $redAgain = $variants->firstWhere('options', ['Color' => 'Red']);
        $this->assertEquals(777, $redAgain->price);
        $this->assertEquals(42, $redAgain->stock);
        $this->assertNotNull($variants->firstWhere('options', ['Color' => 'Green']));
        $this->assertNull($variants->firstWhere('options', ['Color' => 'Blue']));
    }

    public function test_too_many_combinations_is_rejected(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'A', 'option1_values' => implode(',', range(1, 10)),
            'option2_name' => 'B', 'option2_values' => implode(',', range(1, 10)),
            'generate_variants' => '1',
        ])->assertSessionHasErrors('option1_name');
        $this->assertSame(0, ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->count());
    }

    public function test_seller_can_edit_and_delete_variant_rows_on_normal_save(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue', 'generate_variants' => '1',
        ]);
        $variants = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->get();

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'variants' => [
                $variants[0]->id => ['price' => 650, 'sale_price' => 600, 'stock' => 5, 'sku' => 'RED-1'],
                $variants[1]->id => ['delete' => '1'],
            ],
        ])->assertSessionHasNoErrors();

        $remaining = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->get();
        $this->assertCount(1, $remaining);
        $this->assertEquals(650, $remaining[0]->price);
        $this->assertEquals(600, $remaining[0]->sale_price);
        $this->assertSame(5, $remaining[0]->stock);
        $this->assertSame('RED-1', $remaining[0]->sku);
    }

    public function test_product_page_shows_the_variant_picker(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue', 'generate_variants' => '1',
        ]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('Color')->assertSee('Red')->assertSee('Blue');
    }

    public function test_order_uses_the_selected_variants_price_and_decrements_its_own_stock(): void
    {
        $u = $this->seller();
        $p = $this->product($u); // base price 500
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red, Blue', 'generate_variants' => '1',
        ]);
        $red = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->where('options', json_encode(['Color' => 'Red']))->firstOrFail();
        $red->update(['price' => 650, 'stock' => 4]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->post($this->url($u, '/cart/add'), ['product_id' => $p->id, 'variant_id' => $red->id, 'qty' => 2])
            ->assertRedirect($this->url($u, '/cart'));
        $this->get($this->url($u, '/cart'))->assertOk()->assertSee('Color: Red');

        $res = $this->post($this->url($u, '/checkout'), [
            'name' => 'Karim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod',
        ]);
        $order = Order::withoutGlobalScopes()->latest('id')->firstOrFail();
        $res->assertRedirect($u->store->fresh()->storePath('/order/'.$order->token));

        $item = $order->items()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame($red->id, $item->variant_id);
        $this->assertSame('Color: Red', $item->variant_label);
        $this->assertEquals(1300.0, $order->subtotal); // 650 x 2, the variant's own price - not the product's 500
        $this->assertSame(2, $red->fresh()->stock); // 4 - 2 = 2, the variant's stock decremented
        $this->assertSame(10, $p->fresh()->stock); // the product's own stock column untouched

        OrderPlacer::changeStatus($order, 'cancelled');
        $this->assertSame(4, $red->fresh()->stock); // cancelling restores the variant's stock
    }

    public function test_out_of_stock_variant_cannot_be_added_to_cart(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red', 'generate_variants' => '1',
        ]);
        $red = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->firstOrFail();
        $red->update(['stock' => 0]);
        $this->app['auth']->forgetGuards();
        app()->forgetInstance('currentStore');

        $this->post($this->url($u, '/cart/add'), ['product_id' => $p->id, 'variant_id' => $red->id])->assertStatus(422);
    }
}
