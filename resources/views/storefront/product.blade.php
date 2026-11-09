@extends('storefront.layout')
@php
    $url = $store->url().'/p/'.$product->slug;
    $cover = $product->images->first()?->url();
    $pct = ($product->sale_price && $product->price > 0) ? (int) round((1 - $product->sale_price / $product->price) * 100) : 0;
    $isNew = $pct <= 0 && $product->created_at && $product->created_at->diffInDays() <= 14;
    $price = \App\Support\Fmt::money($product->currentPrice());
    $s = $store->settings ?? [];
    $charge = (float) ($s['delivery_charge'] ?? 0); $free = (float) ($s['free_delivery_over'] ?? 0);
    $deliveryEstimate = $s['delivery_estimate'] ?? null;
    $specs = collect($product->specs ?? [])->filter(fn ($row) => filled($row['key'] ?? null) && filled($row['value'] ?? null))->values();
    $sizeGuide = $product->size_guide;
    $wa = \App\Support\Fmt::whatsapp($look['whatsapp'] ?? null);
    $waText = __('store.wa_msg', ['name' => $product->name, 'url' => $url]);
    $waTextOos = __('store.wa_msg_oos', ['name' => $product->name, 'url' => $url]);
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150);

    // variants: each row carries its own already-formatted price text, so the client only ever
    // switches between server-rendered strings - no client-side currency/Bangla-digit logic needed.
    $optionChoices = collect($product->optionNames())->mapWithKeys(fn ($name) => [$name => $product->variants->pluck('options.'.$name)->unique()->values()->all()]);
    $variantsData = $product->variants->map(function ($v) use ($product) {
        if ($v->price !== null) {
            $base = (float) $v->price;
            $eff = (float) ($v->sale_price ?? $v->price);
        } else {
            $base = (float) $product->price;
            $eff = (float) $product->currentPrice();
        }
        $vPct = ($eff < $base && $base > 0) ? (int) round((1 - $eff / $base) * 100) : 0;

        return [
            'id' => $v->id, 'options' => $v->options, 'stock' => $v->stock, 'sku' => $v->sku,
            'priceText' => \App\Support\Fmt::money($eff), 'oldPriceText' => $vPct > 0 ? \App\Support\Fmt::money($base) : null, 'pct' => $vPct,
        ];
    })->values();
    $available = $product->variants->isNotEmpty() ? $product->variants->sum('stock') > 0 : $product->stock > 0;
    $template = in_array($store->product_template, array_keys(\App\Models\Store::PRODUCT_TEMPLATES), true) ? $store->product_template : 'classic';
@endphp
@section('title', $product->name.' - '.$store->name)
@section('og')
<meta property="og:type" content="product"><meta property="og:site_name" content="{{ $store->name }}"><meta property="og:title" content="{{ $product->name }}">
<meta property="og:description" content="{{ $price }}{{ $desc ? ' · '.$desc : '' }}"><meta property="og:url" content="{{ $url }}">
@if ($cover)<meta property="og:image" content="{{ $cover }}"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="{{ $cover }}">@endif
<meta property="product:price:amount" content="{{ (int) $product->currentPrice() }}"><meta property="product:price:currency" content="{{ $store->settings['currency'] ?? 'BDT' }}">
<meta property="product:availability" content="{{ $available ? 'in stock' : 'out of stock' }}"><meta name="description" content="{{ $desc }}">
@endsection
@section('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => $desc ?: $product->name,
    'image' => $cover ? [$cover] : [],
    'sku' => $product->slug,
    'offers' => [
        '@type' => 'Offer',
        'url' => $url,
        'priceCurrency' => $store->settings['currency'] ?? 'BDT',
        'price' => (string) $product->currentPrice(),
        'availability' => $available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
], JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
@section('hide_fab', '1')
@section('content')
@include('storefront.product-templates.'.$template)

{{-- client-only "recently viewed" strip: no backend, pure localStorage, so it costs nothing at scale --}}
<section id="sf-recent-wrap" class="mt-2 mb-28 md:mb-8 hidden">
    <h2 class="text-xl font-semibold mb-3">{{ __('store.recently_viewed') }}</h2>
    <div id="sf-recent-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4"></div>
</section>
<script>
(function () {
    try {
        if (window.sfRecent) {
            window.sfRecent.push({{ $product->id }});
            window.sfRecent.render('sf-recent-wrap', 'sf-recent-grid', {{ $product->id }});
        }
    } catch (e) {}
})();
</script>
@endsection
