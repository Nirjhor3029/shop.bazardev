<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $slug = 'alpha'): User
    {
        $u = User::factory()->create();
        $s = (new StoreCreator)->create($u, $slug, ucfirst($slug));
        $s->update(['status' => 'published']);

        return $u->fresh();
    }

    private function product(User $u): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $u->store->id)->orderBy('id')->firstOrFail();
    }

    private function url(User $u, string $path): string
    {
        return $u->store->fresh()->url().$path;
    }

    public function test_specs_are_parsed_from_key_value_lines_blank_and_malformed_lines_skipped_and_capped_at_15(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $lines = array_map(fn ($i) => "Spec $i: Value $i", range(1, 20));
        $raw = implode("\n", array_merge($lines, ['', 'No colon here', '  : empty key', 'Key only:  ']));

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock, 'specs' => $raw,
        ])->assertSessionHasNoErrors();

        $specs = $p->refresh()->specs;
        $this->assertCount(15, $specs); // capped
        $this->assertSame(['key' => 'Spec 1', 'value' => 'Value 1'], $specs[0]);
    }

    public function test_size_guide_is_saved_and_shown_only_when_filled(): void
    {
        $u = $this->seller();
        $p = $this->product($u);

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertDontSee('Size guide');

        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock, 'is_active' => 1, 'size_guide' => 'M fits 38-40in chest.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('M fits 38-40in chest.', $p->refresh()->size_guide);
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('M fits 38-40in chest.');
    }

    public function test_specs_table_renders_on_product_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $p->update(['specs' => [['key' => 'Material', 'value' => 'Cotton']]]);

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('Material')->assertSee('Cotton');
    }

    public function test_variant_sku_is_embedded_in_the_product_page_variant_data(): void
    {
        $u = $this->seller();
        $this->upgradeToPro($u->store);
        $p = $this->product($u);
        $this->actingAs($u)->put(route('seller.products.update', $p), [
            'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
            'option1_name' => 'Color', 'option1_values' => 'Red', 'generate_variants' => '1',
        ]);
        $v = ProductVariant::withoutGlobalScopes()->where('product_id', $p->id)->firstOrFail();
        $v->update(['sku' => 'RED-SKU-001']);

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('RED-SKU-001');
    }

    public function test_delivery_estimate_is_saved_and_shown_on_product_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u);

        $this->actingAs($u)->put(route('seller.settings.update'), [
            'name' => $u->store->name, 'delivery_charge' => 60, 'delivery_estimate' => 'Delivered in 2-4 days',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Delivered in 2-4 days', $u->store->fresh()->settings['delivery_estimate']);
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('Delivered in 2-4 days');
    }

    public function test_wishlist_page_renders(): void
    {
        $u = $this->seller();
        $this->get($this->url($u, '/wishlist'))->assertOk()->assertSee('sf-wishlist-grid', false);
    }

    public function test_cards_by_ids_endpoint_renders_requested_products_scoped_to_the_current_store(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $pa = $this->product($a);
        $pb = $this->product($b);
        $pb->update(['name' => 'Beta Only Product']); // sample seeding gives both stores' first product the same default name

        $this->get($this->url($a, '/cards?ids='.$pa->id.','.$pb->id))
            ->assertOk()->assertSee($pa->name)->assertDontSee('Beta Only Product');
    }
}
