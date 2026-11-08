@if ($featured->count() > 0)
<section class="mb-8">
    <h2 class="text-lg font-semibold mb-3">{{ __('store.featured') }}</h2>
    <div class="sf-featured-row">
        @foreach ($featured as $p)<div class="sf-featured-item">@include('storefront._card', ['p' => $p])</div>@endforeach
    </div>
</section>
@endif
