@extends('storefront.layout')
@php
    $url = $store->url().'/p/'.$product->slug;
    $cover = $product->images->first()?->url();
    $pct = ($product->sale_price && $product->price > 0) ? (int) round((1 - $product->sale_price / $product->price) * 100) : 0;
    $price = \App\Support\Fmt::money($product->currentPrice());
    $s = $store->settings ?? [];
    $charge = (float) ($s['delivery_charge'] ?? 0); $free = (float) ($s['free_delivery_over'] ?? 0);
    $wa = \App\Support\Fmt::whatsapp($look['whatsapp'] ?? null);
    $waText = __('store.wa_msg', ['name' => $product->name, 'url' => $url]);
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150);
@endphp
@section('title', $product->name.' - '.$store->name)
@section('og')
<meta property="og:type" content="product"><meta property="og:site_name" content="{{ $store->name }}"><meta property="og:title" content="{{ $product->name }}">
<meta property="og:description" content="{{ $price }}{{ $desc ? ' · '.$desc : '' }}"><meta property="og:url" content="{{ $url }}">
@if ($cover)<meta property="og:image" content="{{ $cover }}"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="{{ $cover }}">@endif
<meta property="product:price:amount" content="{{ (int) $product->currentPrice() }}"><meta property="product:price:currency" content="{{ $store->settings['currency'] ?? 'BDT' }}">
<meta property="product:availability" content="{{ $product->stock > 0 ? 'in stock' : 'out of stock' }}"><meta name="description" content="{{ $desc }}">
@endsection
@section('hide_fab', '1')
@section('content')
<nav class="text-sm text-gray-500 mb-3"><a href="/" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="/?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
<div class="grid md:grid-cols-2 gap-6 md:gap-10">
    {{-- gallery: swipe on phones --}}
    <div x-data="{ i: 0, go(n) { this.$refs.g.scrollTo({ left: n * this.$refs.g.clientWidth, behavior: 'smooth' }) } }">
        <div x-ref="g" @scroll.debounce.50ms="i = Math.round($refs.g.scrollLeft / $refs.g.clientWidth)" class="sf-gallery">
            @forelse ($product->images as $img)<img src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover" @if (! $loop->first) loading="lazy" @endif>@empty<div class="aspect-square bg-gray-100"></div>@endforelse
        </div>
        @if ($product->images->count() > 1)
            <div class="mt-3 flex justify-center gap-2">@foreach ($product->images as $k => $img)<button type="button" @click="go({{ $k }})" class="w-14 h-14 rounded border-2 overflow-hidden" :class="i === {{ $k }} ? 'border-[var(--c)]' : 'border-transparent opacity-70'" aria-label="{{ $k + 1 }}"><img src="{{ $img->url() }}" alt="" class="w-full h-full object-cover"></button>@endforeach</div>
        @endif
    </div>
    <div>
        <h1 class="text-2xl font-bold leading-snug">{{ $product->name }}</h1>
        <p class="mt-3 flex items-center gap-3 flex-wrap"><span class="sf-price text-3xl">{{ $price }}</span>
            @if ($pct > 0)<s class="text-gray-400 text-lg">{{ \App\Support\Fmt::money($product->price) }}</s><span class="sf-tag">-{{ \App\Support\Fmt::digits($pct) }}%</span>@endif</p>
        <p class="mt-2 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
            @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>

        @if ($product->stock > 0)
            <form id="addform" method="POST" action="/cart/add" class="mt-5 flex items-center gap-3">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <label class="sr-only" for="qty">{{ __('store.qty') }}</label>
                <input id="qty" type="number" name="qty" value="1" min="1" max="{{ min(99, $product->stock) }}" class="w-20 border-gray-300 rounded-lg text-center">
                <button class="sf-btn flex-1">{{ __('store.add_to_cart') }}</button>
            </form>
            @if ($wa)<a class="sf-btn sf-btn-wa mt-3 w-full" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener">{{ __('store.order_wa') }}</a>@endif
        @endif

        <div class="mt-5 sf-card p-4 text-sm space-y-3">
            <p><span class="font-semibold">{{ __('store.delivery') }}:</span>
                @if ($charge <= 0){{ __('store.free_delivery') }}
                @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif</p>
            @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ __('store.pay') }}:</span>
                @foreach ($methods as $m)<span class="sf-chip !py-0.5 !text-xs">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
        </div>

        @if ($product->description)<div class="mt-5"><h2 class="font-semibold mb-1">{{ __('store.description') }}</h2><p class="whitespace-pre-line text-gray-700">{{ $product->description }}</p></div>@endif

        <div class="mt-6 flex items-center flex-wrap gap-2 text-sm" x-data="{ ok: false }"><span class="font-semibold mr-1">{{ __('store.share') }}:</span>
            <a class="sf-chip" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
            <a class="sf-chip" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
            <button type="button" class="sf-chip" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button></div>
    </div>
</div>

@if ($related->count())
<section class="mt-12 mb-6"><h2 class="text-xl font-semibold mb-3">{{ __('store.related') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">@foreach ($related as $p)@include('storefront._card')@endforeach</div></section>
@endif
<div class="h-20 md:hidden"></div>
@endsection
@section('bar')
@if ($product->stock > 0)
<div class="sf-bar"><div class="min-w-0"><p class="sf-price text-xl leading-tight">{{ $price }}</p>@if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($product->price) }}</s>@endif</div>
    <button class="sf-btn flex-1" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>
    @if ($wa)<a class="sf-btn sf-btn-wa !px-3" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></a>@endif</div>
@endif
@endsection
