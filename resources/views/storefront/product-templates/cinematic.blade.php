@php $hasImg = $product->images->isNotEmpty(); @endphp
<div x-data="{
        sel: {},
        variants: {{ \Illuminate\Support\Js::from($variantsData) }},
        qty: 1,
        get variant() {
            if (! this.variants.length) return null;
            const names = Object.keys(this.variants[0].options);
            if (names.some(n => ! this.sel[n])) return null;
            return this.variants.find(v => names.every(n => v.options[n] === this.sel[n])) || null;
        },
        get ready() { return ! this.variants.length || !! this.variant; },
        get stock() { return this.variant ? this.variant.stock : {{ $product->stock }}; },
        get max() { return Math.min(99, this.stock); },
        get priceText() { return this.variant ? this.variant.priceText : {{ \Illuminate\Support\Js::from($price) }}; },
        get oldPriceText() { return this.variant ? this.variant.oldPriceText : {{ \Illuminate\Support\Js::from($pct > 0 ? \App\Support\Fmt::money($product->price) : null) }}; },
        get pct() { return this.variant ? this.variant.pct : {{ $pct }}; },
    }">
    {{-- full-bleed hero: breaks out of the max-w container, Apple/Tesla-style quiet, product-first opening --}}
    <div class="relative left-1/2 -ml-[50vw] w-screen">
        @if ($hasImg)
            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="w-full max-h-[70vh] object-cover sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = {{ \Illuminate\Support\Js::from($product->images->first()->url()) }}; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}">
        @else
            <div class="w-full h-[50vh] bg-gray-100 flex items-center justify-center text-gray-400">{{ __('store.no_image') }}</div>
        @endif
        @unless ($available)<span class="absolute inset-0 bg-white/60 flex items-center justify-center font-semibold text-gray-700 text-lg">{{ __('store.out') }}</span>@endunless
        <template x-if="variant && variant.image"><img :src="variant.image" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = variant.image; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}"></template>
        <button type="button" class="sf-wish-btn" data-wish-id="{{ $product->id }}" aria-label="{{ __('store.wishlist_add') }}" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
    </div>

    <nav class="text-sm text-gray-400 mt-6"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>

    <div class="max-w-lg mx-auto text-center mt-6">
        <h1 class="text-3xl md:text-4xl font-semibold tracking-tight">{{ $product->name }}</h1>
        @if ($product->description)<p class="mt-3 text-gray-500">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 160) }}</p>@endif

        <p class="mt-6 flex items-center justify-center gap-3 flex-wrap"><span class="sf-price text-3xl" x-text="priceText">{{ $price }}</span>
            <template x-if="pct > 0"><s class="text-gray-400 text-lg" x-text="oldPriceText"></s></template>
            <template x-if="pct > 0"><span class="sf-tag" x-text="'-' + pct + '%'"></span></template>
        </p>

        @if ($product->images->count() > 1)
            <div class="mt-4 flex justify-center gap-2">@foreach ($product->images->skip(1) as $img)<img src="{{ $img->url() }}" alt="" class="w-16 h-16 rounded-xl object-cover">@endforeach</div>
        @endif

        @if ($product->variants->isNotEmpty())
            <div class="mt-6 space-y-3 text-left">
                @foreach ($optionChoices as $name => $values)
                    <div><p class="text-sm font-medium mb-1 text-center">{{ $name }}</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            @foreach ($values as $val)
                                <button type="button" class="sf-chip" :class="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'on' : ''" @click="sel[{{ \Illuminate\Support\Js::from($name) }}] = {{ \Illuminate\Support\Js::from($val) }}">{{ $val }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="text-sm text-amber-600 text-center" x-show="! ready">{{ __('store.choose_options', ['names' => $optionChoices->keys()->implode(' / ')]) }}</p>
            </div>
        @else
            <p class="mt-2 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
                @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
        @endif
        <template x-if="variant && variant.sku"><p class="text-xs text-gray-400">{{ __('store.sku') }}: <span x-text="variant.sku"></span></p></template>

        @if ($available)
            <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-6 flex items-center justify-center gap-3">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" :value="variant?.id ?? ''">
                <div class="flex items-center border border-gray-300 rounded-full shrink-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                    <input type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-12 border-0 text-center focus:ring-0 p-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                </div>
                <button class="sf-btn px-10 rounded-full" :disabled="! ready || stock < 1" :class="(! ready || stock < 1) ? 'opacity-50 cursor-not-allowed' : ''">{{ __('store.add_to_cart') }}</button>
            </form>
        @endif
        @if ($wa)<a class="mt-3 inline-block text-sm underline text-gray-500" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ $available ? __('store.order_wa') : __('store.ask_wa') }}</a>@endif

        <div class="mt-8 text-sm text-gray-500 space-y-1 text-left sf-card p-4">
            <p><span class="font-medium text-gray-700">{{ __('store.delivery') }}:</span>
                @if ($charge <= 0){{ __('store.free_delivery') }}
                @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif
                @if ($deliveryEstimate) · {{ $deliveryEstimate }}@endif</p>
            @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-medium text-gray-700">{{ __('store.pay') }}:</span>
                @foreach ($methods as $m)<span class="sf-chip !py-0.5 !text-xs">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
        </div>

        @if ($product->description)<div class="mt-6 text-left"><h2 class="font-semibold mb-1">{{ __('store.description') }}</h2><p class="whitespace-pre-line text-gray-700 text-sm">{{ $product->description }}</p></div>@endif
        <div class="text-left">@include('storefront.product-templates._specs') @include('storefront.product-templates._reviews') @include('storefront.product-templates._qna') @include('storefront.product-templates._bundle')</div>

        <div class="mt-6 flex items-center justify-center flex-wrap gap-2 text-sm" x-data="{ ok: false }" aria-live="polite"><span class="font-semibold mr-1">{{ __('store.share') }}:</span>
            <a class="sf-chip" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
            <a class="sf-chip" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
            <button type="button" class="sf-chip" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button></div>
    </div>
</div>

@if ($related->count())
<section class="mt-12 mb-6"><h2 class="text-xl font-semibold mb-3 text-center">{{ __('store.related') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">@foreach ($related as $p)@include('storefront._card')@endforeach</div></section>
@endif
<div class="h-20 md:hidden"></div>

@if ($available || $wa)
<div class="sf-bar">
    <div class="min-w-0">
        @if ($available)
            <p class="sf-price text-xl leading-tight">{{ $price }}</p>@if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($product->price) }}</s>@endif
        @else
            <p class="text-sm font-semibold text-red-600">{{ __('store.out') }}</p>
        @endif
    </div>
    @if ($available)<button class="sf-btn flex-1 rounded-full" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>@endif
    @if ($wa)
        <a class="sf-btn sf-btn-wa {{ $available ? '!px-3' : 'flex-1' }} rounded-full" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">
            @if ($available)<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>@else{{ __('store.ask_wa') }}@endif
        </a>
    @endif
</div>
@endif
