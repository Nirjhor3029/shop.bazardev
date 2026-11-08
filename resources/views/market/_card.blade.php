@php $url = $p->store->url().'/p/'.$p->slug; $pct = ($p->sale_price && $p->price > 0) ? (int) round((1 - $p->sale_price / $p->price) * 100) : 0; @endphp
<a href="{{ $url }}" class="group block bg-white border rounded-xl overflow-hidden hover:shadow-md transition">
    <div class="relative aspect-square bg-gray-100 overflow-hidden">
        @if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">@endif
        @if ($pct > 0)<span class="absolute top-2 left-2 bg-brand text-white text-xs font-bold rounded px-1.5 py-0.5">-{{ $pct }}%</span>@endif
        @if ($p->stock < 1)<span class="absolute inset-0 bg-white/70 flex items-center justify-center text-sm font-semibold text-gray-600">{{ __('market.out') }}</span>@endif
    </div>
    <div class="p-3">
        <p class="text-sm line-clamp-2 min-h-[2.5rem]">{{ $p->name }}</p>
        <p class="mt-1 font-bold text-brand">{{ \App\Support\Fmt::money($p->currentPrice()) }} @if ($pct > 0)<s class="text-xs font-normal text-gray-400">{{ \App\Support\Fmt::money($p->price) }}</s>@endif</p>
        <p class="mt-1 text-xs text-gray-500 truncate">{{ $p->store->name }}</p>
    </div>
</a>
