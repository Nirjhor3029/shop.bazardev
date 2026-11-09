<nav class="text-sm text-gray-500 mb-3"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
<div class="grid md:grid-cols-2 gap-6 md:gap-10" x-data="{
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
    {{-- gallery: swipe on phones --}}
    <div x-data="{ i: 0, go(n) { this.$refs.g.scrollTo({ left: n * this.$refs.g.clientWidth, behavior: 'smooth' }) } }">
        <div x-ref="g" @scroll.debounce.50ms="i = Math.round($refs.g.scrollLeft / $refs.g.clientWidth)" class="sf-gallery relative">
            @forelse ($product->images as $img)<img src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = {{ \Illuminate\Support\Js::from($img->url()) }}; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}" @if (! $loop->first) loading="lazy" @endif>@empty
                <div class="aspect-square bg-gray-100 flex flex-col items-center justify-center text-gray-400 gap-1">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <span class="text-xs">{{ __('store.no_image') }}</span>
                </div>
            @endforelse
            @unless ($available)<span class="absolute inset-0 bg-white/70 flex items-center justify-center font-semibold text-gray-700 z-10">{{ __('store.out') }}</span>@endunless
            <template x-if="variant && variant.image"><img :src="variant.image" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = variant.image; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}"></template>
            <button type="button" class="sf-wish-btn" data-wish-id="{{ $product->id }}" aria-label="{{ __('store.wishlist_add') }}" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
        </div>
        @if ($product->images->count() > 1)
            <div class="mt-3 flex justify-center gap-2">@foreach ($product->images as $k => $img)<button type="button" @click="go({{ $k }})" class="w-14 h-14 rounded border-2 overflow-hidden" :class="i === {{ $k }} ? 'border-[var(--c)]' : 'border-transparent opacity-70'" aria-label="{{ $k + 1 }}"><img src="{{ $img->url() }}" alt="" class="w-full h-full object-cover"></button>@endforeach</div>
        @endif
    </div>
    <div>
        <h1 class="text-2xl font-bold leading-snug">{{ $product->name }}</h1>
        <p class="mt-3 flex items-center gap-3 flex-wrap"><span class="sf-price text-3xl" x-text="priceText">{{ $price }}</span>
            <template x-if="pct > 0"><s class="text-gray-400 text-lg" x-text="oldPriceText"></s></template>
            <template x-if="pct > 0"><span class="sf-tag" x-text="'-' + pct + '%'"></span></template>
            @if ($pct <= 0 && $isNew)<span class="sf-tag-new" x-show="! variant">{{ __('store.new') }}</span>@endif
        </p>

        @if ($product->variants->isEmpty())
            <p class="mt-2 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
                @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
        @else
            <p class="mt-2 text-sm font-medium" :class="stock > 0 ? 'text-green-700' : 'text-red-600'" x-text="stock > 0 ? {{ \Illuminate\Support\Js::from(__('store.in_stock')) }} : {{ \Illuminate\Support\Js::from(__('store.out')) }}"></p>

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
        <template x-if="variant && variant.sku"><p class="text-xs text-gray-400 mt-1">{{ __('store.sku') }}: <span x-text="variant.sku"></span></p></template>

        @if ($available)
            <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-5 flex items-center gap-3">@csrf
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
        @if ($wa)<a class="sf-btn sf-btn-wa mt-3 w-full" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ $available ? __('store.order_wa') : __('store.ask_wa') }}</a>@endif

        <div class="mt-5 sf-card p-4 text-sm space-y-3">
            <p><span class="font-semibold">{{ __('store.delivery') }}:</span>
                @if ($charge <= 0){{ __('store.free_delivery') }}
                @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif
                @if ($deliveryEstimate) · {{ $deliveryEstimate }}@endif</p>
            @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ __('store.pay') }}:</span>
                @foreach ($methods as $m)<span class="sf-chip !py-0.5 !text-xs">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
        </div>

        @if ($product->description)<div class="mt-5"><h2 class="font-semibold mb-1">{{ __('store.description') }}</h2><p class="whitespace-pre-line text-gray-700">{{ $product->description }}</p></div>@endif
        @include('storefront.product-templates._specs')
        @include('storefront.product-templates._reviews')
        @include('storefront.product-templates._qna')
        @include('storefront.product-templates._bundle')

        <div class="mt-6 flex items-center flex-wrap gap-2 text-sm" x-data="{ ok: false }" aria-live="polite"><span class="font-semibold mr-1">{{ __('store.share') }}:</span>
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

@if ($available || $wa)
<div class="sf-bar">
    <div class="min-w-0">
        @if ($available)
            <p class="sf-price text-xl leading-tight">{{ $price }}</p>@if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($product->price) }}</s>@endif
        @else
            <p class="text-sm font-semibold text-red-600">{{ __('store.out') }}</p>
        @endif
    </div>
    @if ($available)<button class="sf-btn flex-1" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>@endif
    @if ($wa)
        <a class="sf-btn sf-btn-wa {{ $available ? '!px-3' : 'flex-1' }}" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">
            @if ($available)<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>@else{{ __('store.ask_wa') }}@endif
        </a>
    @endif
</div>
@endif
