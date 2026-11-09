<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
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

    /** Places a real order through checkout (same path as OrderFlowTest) so the review is gated by a genuine order+token. */
    private function deliveredOrder(User $u, Product $p, string $status = 'delivered'): Order
    {
        $this->withSession(['cart.'.$u->store->id => [$p->id => 1]])
            ->post($this->url($u, '/checkout'), ['name' => 'Rahim', 'phone' => '01712345678', 'address' => 'Dhaka', 'payment_method' => 'cod']);
        $order = Order::withoutGlobalScopes()->firstOrFail();
        $order->update(['status' => $status]);

        return $order;
    }

    public function test_review_can_be_submitted_for_a_delivered_order_and_awaits_moderation(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $order = $this->deliveredOrder($u, $p);

        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 5, 'comment' => 'Great!'])
            ->assertRedirect();

        $review = ProductReview::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame(5, $review->rating);
        $this->assertSame(0, $p->refresh()->reviews_count); // not counted until approved
    }

    public function test_review_is_rejected_before_the_order_is_delivered(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $order = $this->deliveredOrder($u, $p, 'pending');

        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 4]);

        $this->assertSame(0, ProductReview::withoutGlobalScopes()->count());
    }

    public function test_duplicate_review_for_the_same_order_and_product_is_blocked(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $order = $this->deliveredOrder($u, $p);

        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 5]);
        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 1]);

        $this->assertSame(1, ProductReview::withoutGlobalScopes()->count());
        $this->assertSame(5, ProductReview::withoutGlobalScopes()->first()->rating);
    }

    public function test_reviewing_a_product_not_in_the_order_is_forbidden(): void
    {
        $u = $this->seller();
        $p = $this->product($u, 1);
        $other = $this->product($u, 2);
        $order = $this->deliveredOrder($u, $p);

        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$other->id), ['rating' => 5])->assertForbidden();
    }

    public function test_seller_approving_a_review_recomputes_the_cached_rating_and_it_appears_on_the_product_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $order = $this->deliveredOrder($u, $p);
        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 4, 'comment' => 'Nice quality']);
        $review = ProductReview::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($u)->put(route('seller.reviews.update', $review), ['status' => 'approved'])->assertRedirect();

        $p->refresh();
        $this->assertSame(1, $p->reviews_count);
        $this->assertEquals(4, $p->rating_avg);
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('Nice quality');
    }

    public function test_rejected_review_does_not_count_toward_the_rating(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $order = $this->deliveredOrder($u, $p);
        $this->post($this->url($u, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 2]);
        $review = ProductReview::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($u)->put(route('seller.reviews.update', $review), ['status' => 'rejected']);

        $this->assertSame(0, $p->refresh()->reviews_count);
    }

    public function test_a_seller_cannot_moderate_another_stores_review(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $p = $this->product($a);
        $order = $this->deliveredOrder($a, $p);
        $this->post($this->url($a, '/order/'.$order->token.'/review/'.$p->id), ['rating' => 3]);
        $review = ProductReview::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($b)->put(route('seller.reviews.update', $review), ['status' => 'approved'])->assertNotFound();
    }
}
