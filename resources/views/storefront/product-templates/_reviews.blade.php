@if ($product->reviews_count > 0)
<div class="mt-6 pt-6 border-t" id="sf-reviews">
    <h2 class="font-semibold mb-3">{{ __('store.reviews') }} · {{ number_format((float) $product->rating_avg, 1) }} ★ ({{ __('store.rating_count', ['count' => \App\Support\Fmt::digits($product->reviews_count)]) }})</h2>
    @foreach ($approvedReviews as $r)
        <div class="py-2 border-b border-gray-100 last:border-0">
            <p class="text-sm">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }} <span class="text-gray-500">{{ $r->customer_name }}</span></p>
            @if ($r->comment)<p class="text-sm text-gray-700 mt-0.5">{{ $r->comment }}</p>@endif
        </div>
    @endforeach
</div>
@endif
