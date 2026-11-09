<nav class="text-sm text-gray-500 mb-3"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
<div x-data="{
        sel: {},
        variants: {{ \Illuminate\Support\Js::from($variantsData) }},
        qty: 1,
        tab: 'overview',
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
    <div class="grid md:grid-cols-2 gap-6 md:gap-10">
        {{-- left: big image with a vertical thumbnail rail (Alibaba's signature gallery) --}}
        <div class="flex gap-3" x-data="{ i: 0 }">
            @if ($product->images->count() > 1)
                <div class="hidden md:flex flex-col gap-2 shrink-0">
                    @foreach ($product->images as $k => $img)<button type="button" @click="i = {{ $k }}" class="w-16 h-16 rounded border-2 overflow-hidden" :class="i === {{ $k }} ? 'border-[var(--c)]' : 'border-transparent opacity-70'" aria-label="{{ $k + 1 }}"><img src="{{ $img->url() }}" alt="" class="w-full h-full object-cover"></button>@endforeach
                </div>
            @endif
            <div class="relative flex-1">
                @forelse ($product->images as $k => $img)
                    <img x-show="i === {{ $k }}" src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-xl sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = {{ \Illuminate\Support\Js::from($img->url()) }}; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}" @if (! $loop->first) loading="lazy" @endif>
                @empty
                    <div class="aspect-square bg-gray-100 rounded-xl flex flex-col items-center justify-center text-gray-400 gap-1">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        <span class="text-xs">{{ __('store.no_image') }}</span>
                    </div>
                @endforelse
                @unless ($available)<span class="absolute inset-0 bg-white/70 flex items-center justify-center font-semibold text-gray-700 z-10 rounded-xl">{{ __('store.out') }}</span>@endunless
                <template x-if="variant && variant.image"><img :src="variant.image" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover rounded-xl sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = variant.image; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}"></template>
                <button type="button" class="sf-wish-btn" data-wish-id="{{ $product->id }}" aria-label="{{ __('store.wishlist_add') }}" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
            </div>
            @if ($product->images->count() > 1)
                <div class="md:hidden flex gap-2 overflow-x-auto absolute">@foreach ($product->images as $k => $img)<button type="button" @click="i = {{ $k }}" class="w-10 h-10 rounded border-2 overflow-hidden shrink-0" :class="i === {{ $k }} ? 'border-[var(--c)]' : 'border-transparent opacity-70'"><img src="{{ $img->url() }}" alt="" class="w-full h-full object-cover"></button>@endforeach</div>
            @endif
        </div>

        <div>
            <h1 class="text-xl font-bold leading-snug">{{ $product->name }}</h1>
            <p class="mt-3 flex items-center gap-3 flex-wrap"><span class="sf-price text-2xl" x-text="priceText">{{ $price }}</span>
                <template x-if="pct > 0"><s class="text-gray-400" x-text="oldPriceText"></s></template>
                <template x-if="pct > 0"><span class="sf-tag" x-text="'-' + pct + '%'"></span></template>
            </p>
            @if ($product->variants->isEmpty())
                <p class="mt-1 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
                    @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
            @else
                <p class="mt-1 text-sm font-medium" :class="stock > 0 ? 'text-green-700' : 'text-red-600'" x-text="stock > 0 ? {{ \Illuminate\Support\Js::from(__('store.in_stock')) }} : {{ \Illuminate\Support\Js::from(__('store.out')) }}"></p>
                <div class="mt-3 space-y-3">
                    @foreach ($optionChoices as $name => $values)
                        <div><p class="text-sm font-medium mb-1">{{ $name }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($values as $val)
                                    <button type="button" class="sf-chip" :class="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'on' : ''" @click="sel[{{ \Illuminate\Support\Js::from($name) }}] = {{ \Illuminate\Support\Js::from($val) }}">{{ $val }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p class="text-sm text-amber-600" x-show="! ready">{{ __('store.choose_options', ['names' => $optionChoices->keys()->implode(' / ')]) }}</p>
                </div>
            @endif
            <template x-if="variant && variant.sku"><p class="mt-1 text-xs text-gray-400">{{ __('store.sku') }}: <span x-text="variant.sku"></span></p></template>

            {{-- supplier-contact-first CTA, Alibaba's B2B feel --}}
            @if ($wa)<a class="sf-btn sf-btn-wa mt-4 w-full" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ __('store.chat_to_order') }}</a>@endif

            @if ($available)
                <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-3 flex items-center gap-3">@csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variant_id" :value="variant?.id ?? ''">
                    <div class="flex items-center border border-gray-300 rounded-lg shrink-0">
                        <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                        <label class="sr-only" for="qty">{{ __('store.qty') }}</label>
                        <input id="qty" type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-12 border-0 text-center focus:ring-0 p-0">
                        <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                    </div>
                    <button class="sf-btn flex-1" :disabled="! ready || stock < 1" :class="(! ready || stock < 1) ? 'opacity-50 cursor-not-allowed' : ''">{{ __('store.add_to_cart') }}</button>
                </form>
            @endif

            @if ($methods->count())
                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm"><span class="font-semibold">{{ __('store.trust_note') }}:</span>
                    @foreach ($methods as $m)<span class="sf-trust-chip">{{ __('store.'.$m) }}</span>@endforeach</div>
            @endif

            <div class="mt-4 flex items-center flex-wrap gap-2 text-sm" x-data="{ ok: false }" aria-live="polite"><span class="font-semibold mr-1">{{ __('store.share') }}:</span>
                <a class="sf-chip" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
                <a class="sf-chip" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
                <button type="button" class="sf-chip" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button></div>
        </div>
    </div>

    {{-- tabbed content, Alibaba-style --}}
    <div class="mt-10 border-b flex gap-1 overflow-x-auto">
        <button type="button" class="sf-tab" :class="tab === 'overview' ? 'on' : ''" @click="tab = 'overview'">{{ __('store.overview') }}</button>
        <button type="button" class="sf-tab" :class="tab === 'shipping' ? 'on' : ''" @click="tab = 'shipping'">{{ __('store.shipping_payment') }}</button>
    </div>
    <div class="py-5" x-show="tab === 'overview'">
        @if ($product->description)<p class="whitespace-pre-line text-gray-700">{{ $product->description }}</p>@else<p class="text-gray-500">{{ $product->name }}</p>@endif
        @include('storefront.product-templates._specs')
        @include('storefront.product-templates._reviews')
        @include('storefront.product-templates._qna')
        @include('storefront.product-templates._bundle')
    </div>
    <div class="py-5 text-sm space-y-2" x-show="tab === 'shipping'">
        <p><span class="font-semibold">{{ __('store.delivery') }}:</span>
            @if ($charge <= 0){{ __('store.free_delivery') }}
            @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif
            @if ($deliveryEstimate) · {{ $deliveryEstimate }}@endif</p>
        @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ __('store.pay') }}:</span>
            @foreach ($methods as $m)<span class="sf-chip !py-0.5 !text-xs">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
    </div>
</div>

@if ($related->count())
<section class="mt-6 mb-6"><h2 class="text-xl font-semibold mb-3">{{ __('store.related') }}</h2>
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
    @if ($wa)
        <a class="sf-btn sf-btn-wa flex-1" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ __('store.chat_to_order') }}</a>
    @endif
    @if ($available)<button class="sf-btn {{ $wa ? '!px-3' : 'flex-1' }}" type="submit" form="addform" aria-label="{{ __('store.add_to_cart') }}">🛒</button>@endif
</div>
@endif
