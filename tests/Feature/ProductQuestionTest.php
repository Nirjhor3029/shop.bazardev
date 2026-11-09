<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use App\Services\StoreCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuestionTest extends TestCase
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

    public function test_anyone_can_ask_a_question_without_an_order_and_it_awaits_an_answer(): void
    {
        $u = $this->seller();
        $p = $this->product($u);

        $this->post($this->url($u, '/p/'.$p->slug.'/question'), ['name' => 'Karim', 'question' => 'Does this come in blue?'])
            ->assertRedirect();

        $q = ProductQuestion::withoutGlobalScopes()->firstOrFail();
        $this->assertNull($q->answer);
        $this->assertFalse($q->is_hidden);
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertDontSee('Does this come in blue?');
    }

    public function test_seller_answering_makes_the_question_visible_on_the_product_page(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->post($this->url($u, '/p/'.$p->slug.'/question'), ['name' => 'Karim', 'question' => 'Does this come in blue?']);
        $q = ProductQuestion::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($u)->put(route('seller.questions.update', $q), ['answer' => 'Yes, blue is available.'])->assertRedirect();

        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertSee('Does this come in blue?')->assertSee('Yes, blue is available.');
    }

    public function test_seller_can_hide_a_spam_question_instead_of_answering(): void
    {
        $u = $this->seller();
        $p = $this->product($u);
        $this->post($this->url($u, '/p/'.$p->slug.'/question'), ['name' => 'Spammer', 'question' => 'Buy cheap followers here']);
        $q = ProductQuestion::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($u)->put(route('seller.questions.update', $q), ['hide' => 1]);

        $this->assertTrue($q->refresh()->is_hidden);
        $this->get($this->url($u, '/p/'.$p->slug))->assertOk()->assertDontSee('Buy cheap followers here');
    }

    public function test_a_seller_cannot_answer_another_stores_question(): void
    {
        $a = $this->seller('alpha');
        $b = $this->seller('beta');
        $p = $this->product($a);
        $this->post($this->url($a, '/p/'.$p->slug.'/question'), ['name' => 'Karim', 'question' => 'Any discount?']);
        $q = ProductQuestion::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($b)->put(route('seller.questions.update', $q), ['answer' => 'Hijacked'])->assertNotFound();
    }
}
