<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBundleTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    private function product(User $u, int $n = 1): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->orderBy('id')->skip($n - 1)->firstOrFail();
    }

    private function url(User $u, string $path): string
    {
        return $u->store->fresh()->url().$path;
    }

    public function test_seller_can_set_companions_and_they_render_on_the_product_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u, 1);
        $c = $this->product($u, 2);

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock, 'is_active' => 1, 'bundle_ids' => [$c->id],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($p->companions()->whereKey($c->id)->exists());
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee($c->name)->assertSee('sf-bundle', false);
    }

    public function test_companions_are_capped_at_four(): void
    {
        $u = $this->seller();
        $p = $this->product($u, 1);
        for ($i = 0; $i < 6; $i++) {
            Product::forceCreate(['store_id' => $p->store_id, 'name' => "Extra $i", 'slug' => "extra-$i", 'price' => 100, 'stock' => 5, 'is_active' => true]);
        }
        $ids = Product::withoutGlobalScopes()->where('store_id', $u->store->id)->where('id', '!=', $p->id)->pluck('id')->all();

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock, 'is_active' => 1, 'bundle_ids' => $ids,
        ]);

        $this->assertSame(4, $p->companions()->count());
    }

    public function test_a_companion_from_another_store_is_rejected(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $p = $this->product($a);
        $foreign = $this->product($b);

        $this->actingAs($a)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock, 'is_active' => 1, 'bundle_ids' => [$foreign->id],
        ]);

        $this->assertSame(0, $p->companions()->count());
    }

    public function test_add_bundle_adds_all_selected_products_to_the_cart(): void
    {
        $u = $this->seller();
        $p = $this->product($u, 1);
        $c = $this->product($u, 2);
        $p->companions()->sync([$c->id => ['sort_order' => 0]]);

        $this->post($this->url($u, '/cart/add-bundle'), ['product_ids' => [$p->id, $c->id]])->assertRedirect();

        $this->get($this->url($u, '/cart'))->assertOk()->assertSee($p->name)->assertSee($c->name);
    }

    public function test_bundle_section_is_hidden_when_the_primary_product_has_variants(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $p = $this->product($u, 1);
        $c = $this->product($u, 2);
        $p->companions()->sync([$c->id => ['sort_order' => 0]]);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red', 'generate_variants' => '1',
        ]);

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertDontSee('sf-bundle', false);
    }
}
