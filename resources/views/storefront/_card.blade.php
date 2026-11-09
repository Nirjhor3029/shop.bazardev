@php
    $pct = ($p->sale_price && $p->price > 0) ? (int) round((1 - $p->sale_price / $p->price) * 100) : 0;
    $isNew = $pct <= 0 && $p->created_at && $p->created_at->diffInDays() <= 14;
@endphp
<div class="relative">
<a href="{{ $store->storePath('/p/'.$p->slug) }}" class="sf-card block overflow-hidden">
    <div class="relative aspect-square bg-gray-100 overflow-hidden">
        @if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover">@endif
        @if ($pct > 0)<span class="absolute top-2 left-2 sf-tag">-{{ \App\Support\Fmt::digits($pct) }}%</span>@elseif ($isNew)<span class="absolute top-2 left-2 sf-tag-new">{{ __('store.new') }}</span>@endif
        @if ($p->stock < 1)<span class="absolute inset-0 bg-white/70 flex items-center justify-center font-semibold text-gray-700">{{ __('store.out') }}</span>@endif
    </div>
    <div class="p-3">
        <h2 class="text-sm md:text-base leading-snug line-clamp-2 min-h-[2.5rem]">{{ $p->name }}</h2>
        <p class="mt-1"><span class="sf-price text-lg">{{ \App\Support\Fmt::money($p->currentPrice()) }}</span> @if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($p->price) }}</s>@endif</p>
        @if ($p->stock > 0 && $p->stock <= 3)<p class="text-xs text-red-600 font-medium">{{ __('store.only_left', ['n' => \App\Support\Fmt::digits($p->stock)]) }}</p>@endif
    </div>
</a>
<button type="button" class="sf-wish-btn" data-wish-id="{{ $p->id }}" aria-label="{{ __('store.wishlist_add') }}" aria-pressed="false">
    <svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
</button>
</div>
