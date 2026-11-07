@php $pct = ($p->sale_price && $p->price > 0) ? (int) round((1 - $p->sale_price / $p->price) * 100) : 0; @endphp
<a href="/p/{{ $p->slug }}" class="sf-card block overflow-hidden">
    <div class="relative aspect-square bg-gray-100 overflow-hidden">
        @if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover">@endif
        @if ($pct > 0)<span class="absolute top-2 left-2 sf-tag">-{{ \App\Support\Fmt::digits($pct) }}%</span>@endif
        @if ($p->stock < 1)<span class="absolute inset-0 bg-white/70 flex items-center justify-center font-semibold text-gray-700">{{ __('store.out') }}</span>@endif
    </div>
    <div class="p-3">
        <h2 class="text-sm md:text-base leading-snug line-clamp-2 min-h-[2.5rem]">{{ $p->name }}</h2>
        <p class="mt-1"><span class="sf-price text-lg">{{ \App\Support\Fmt::money($p->currentPrice()) }}</span> @if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($p->price) }}</s>@endif</p>
        @if ($p->stock > 0 && $p->stock <= 3)<p class="text-xs text-red-600 font-medium">{{ __('store.only_left', ['n' => \App\Support\Fmt::digits($p->stock)]) }}</p>@endif
    </div>
</a>
