@php
    $cover = $product->coverUrl();
    $price = \App\Support\Fmt::money($product->currentPrice());
    $pct = ($product->sale_price && $product->price > 0) ? (int) round((1 - $product->sale_price / $product->price) * 100) : 0;
    $headline = $lp->headline ?: $product->name;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $headline }} - {{ $store->name }}</title>
<meta property="og:type" content="product"><meta property="og:title" content="{{ $headline }}"><meta property="og:site_name" content="{{ $store->name }}">
@if ($cover)<meta property="og:image" content="{{ $cover }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
:root { --c: {{ $color }}; --c-dark: color-mix(in srgb, var(--c) 82%, black); --c-soft: color-mix(in srgb, var(--c) 12%, white); }
body { background:#f9fafb; color:#111827; font-family: 'Hind Siliguri', ui-sans-serif, system-ui, sans-serif; font-feature-settings: 'tnum'; }
.lp-card { background:#fff; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
.lp-btn { background: var(--c); color:#fff; border-radius:10px; padding:.8rem 1.4rem; font-weight:700; display:flex; align-items:center; justify-content:center; width:100%; min-height:48px; }
.lp-price { font-weight:700; color: var(--c); }
.lp-tag { display:inline-block; background:#fef2f2; color:#b91c1c; font-weight:700; font-size:.75rem; border-radius:6px; padding:.1rem .45rem; }
.lp-chip { border:1px solid #d1d5db; border-radius:999px; padding:.3rem 1rem; font-size:.85rem; background:#fff; }
.lp-bar { position:fixed; left:0; right:0; bottom:0; z-index:35; background:#fff; border-top:1px solid #e5e7eb; padding:.6rem 1rem calc(.6rem + env(safe-area-inset-bottom)); }
@media (min-width: 768px) { .lp-bar { display:none; } }
</style></head>
<body class="antialiased">
<main class="max-w-xl mx-auto px-4 py-5 pb-24 md:pb-10">
    @php $isCombo = $lp->template === 'combo' && ! empty($lp->combos); @endphp
    @if ($lp->template === 'video' && $lp->youtubeEmbedUrl())
        <div class="aspect-video rounded-xl overflow-hidden"><iframe src="{{ $lp->youtubeEmbedUrl() }}" class="w-full h-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
    @elseif ($lp->template === 'video' && $lp->video_url)
        <video src="{{ $lp->video_url }}" controls class="w-full aspect-video rounded-xl object-cover"></video>
    @elseif ($cover)
        <img src="{{ $cover }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-xl">
    @endif

    <h1 class="mt-4 text-2xl font-bold leading-snug">{{ $headline }}</h1>
    @if ($lp->subheadline)<p class="mt-1 text-gray-600">{{ $lp->subheadline }}</p>@endif

    @unless ($isCombo)
    <p class="mt-3 flex items-center gap-3 flex-wrap"><span class="lp-price text-3xl">{{ $price }}</span>
        @if ($pct > 0)<s class="text-gray-400 text-lg">{{ \App\Support\Fmt::money($product->price) }}</s><span class="lp-tag">-{{ \App\Support\Fmt::digits($pct) }}%</span>@endif</p>
    @endunless
    <p class="mt-1 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
        @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>

    @if (! empty($lp->benefits))
        <ul class="mt-4 space-y-2">
            @foreach ($lp->benefits as $b)
                <li class="flex items-start gap-2 text-sm"><svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><span>{{ $b }}</span></li>
            @endforeach
        </ul>
    @endif

    <div class="mt-5 lp-card p-4 text-sm space-y-2">
        <p><span class="font-semibold">{{ __('store.delivery') }}:</span>
            @if ($delivery <= 0){{ __('store.free_delivery') }}@else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($delivery)]) }}@endif</p>
        @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ __('store.pay') }}:</span>
            @foreach ($methods as $m)<span class="lp-chip !py-0.5 !text-xs">{{ __('store.'.$m->type) }}</span>@endforeach</p>@endif
    </div>

    @if ($product->stock > 0)
    <form id="lp-order" method="POST" action="{{ url()->current() }}/order" class="mt-6 lp-card p-4 space-y-4" x-data="{ pm: '{{ old('payment_method', $methods->first()?->type) }}', qty: 1, max: {{ min(99, $product->stock) }} }">@csrf
        <h2 class="font-semibold text-lg">{{ __('store.place_order') }}</h2>
        @error('cart')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

        @if ($isCombo)
            <div><label class="block text-sm font-medium mb-2">{{ __('store.qty') }}</label>
                <div class="space-y-2">
                    @foreach ($lp->combos as $i => $c)
                        <label class="flex items-center justify-between gap-3 border rounded-lg p-3 cursor-pointer">
                            <span class="flex items-center gap-2"><input type="radio" name="combo" value="{{ $i }}" @checked(old('combo', 0) == $i) required>
                                <span class="font-medium">{{ \App\Support\Fmt::digits($c['qty']) }} {{ __('store.qty') }}@if (! empty($c['label'])) · {{ $c['label'] }}@endif</span></span>
                            <span class="lp-price">{{ \App\Support\Fmt::money($c['price']) }}</span>
                        </label>
                    @endforeach
                </div>@error('combo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @else
            <div><label class="block text-sm font-medium">{{ __('store.qty') }}</label>
                <div class="mt-1 flex items-center border border-gray-300 rounded-lg w-fit">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                    <input type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-12 border-0 text-center focus:ring-0 p-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                </div>
            </div>
        @endif

        @foreach ([['name','name','text','name'],['phone','mobile','tel','tel'],['email','email_opt','email','email']] as [$f,$l,$t,$ac])
            <div><label class="block text-sm font-medium" for="f-{{ $f }}">{{ __('store.'.$l) }}</label><input id="f-{{ $f }}" type="{{ $t }}" name="{{ $f }}" value="{{ old($f) }}" autocomplete="{{ $ac }}" @if ($f !== 'email') required @endif class="mt-1 border-gray-300 rounded-lg w-full">@error($f)<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        @endforeach
        <div><label class="block text-sm font-medium" for="f-address">{{ __('store.full_address') }}</label><textarea id="f-address" name="address" rows="2" required autocomplete="street-address" class="mt-1 border-gray-300 rounded-lg w-full">{{ old('address') }}</textarea>@error('address')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-medium" for="f-note">{{ __('store.note_opt') }}</label><input id="f-note" name="note" value="{{ old('note') }}" class="mt-1 border-gray-300 rounded-lg w-full"></div>

        <div>
            <p class="text-sm font-medium mb-2">{{ __('store.pay') }}</p>
            @foreach ($methods as $m)
                <label class="block border rounded-lg p-3 mb-2 cursor-pointer">
                    <input type="radio" name="payment_method" value="{{ $m->type }}" x-model="pm" class="mr-2">
                    <span class="font-medium">{{ __('store.'.$m->type) }}</span>
                    <span class="block text-sm text-gray-600 ml-6" x-show="pm === '{{ $m->type }}'">
                        @if ($m->type === 'cod'){{ $m->instructions ?: ($isCombo ? __('store.pay_cod_note_generic') : __('store.pay_cod_note', ['amount' => $price])) }}
                        @else{{ $isCombo ? __('store.send_money_generic', ['number' => \App\Support\Fmt::digits($m->number)]) : __('store.send_money', ['amount' => $price, 'number' => \App\Support\Fmt::digits($m->number)]) }} {{ $m->instructions }}@endif
                    </span>
                </label>
            @endforeach
            @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <div x-show="pm !== 'cod'" class="space-y-3 mt-3">
                <div><label class="block text-sm font-medium" for="f-trx">{{ __('store.trx_id') }}</label><input id="f-trx" name="trx_id" value="{{ old('trx_id') }}" maxlength="40" class="mt-1 border-gray-300 rounded-lg w-full">@error('trx_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium" for="f-payer">{{ __('store.sent_from') }}</label><input id="f-payer" name="payer_number" value="{{ old('payer_number') }}" class="mt-1 border-gray-300 rounded-lg w-full">@error('payer_number')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <button class="lp-btn" type="submit" form="lp-order">{{ __('store.place_order') }}</button>
    </form>
    @else
    <div class="mt-6 lp-card p-4 text-center text-red-600 font-medium">{{ __('store.out') }}</div>
    @endif
</main>
@if ($product->stock > 0)
<div class="lp-bar"><button class="lp-btn" type="submit" form="lp-order">{{ __('store.place_order') }}@unless ($isCombo) · {{ $price }}@endunless</button></div>
@endif
</body></html>
