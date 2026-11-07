<section id="products" class="mb-10">
    @if ($activeCategory)<h2 class="text-xl font-semibold mb-3">{{ $activeCategory->name }}</h2>@endif
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
        @forelse ($products as $p)
            @include('storefront._card')
        @empty<p class="col-span-full text-gray-500">{{ __('store.no_products') }}</p>@endforelse
    </div>
</section>
