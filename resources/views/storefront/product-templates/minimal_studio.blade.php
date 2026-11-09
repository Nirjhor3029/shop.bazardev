<nav class="text-sm text-gray-400 mb-4"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
<div class="grid md:grid-cols-2 gap-10 md:gap-20" x-data="{
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
    {{-- one quiet image, no ribbons/badges/motion - minimalist brands let the product speak --}}
    <div x-data="{ i: 0, go(n) { this.$refs.g.scrollTo({ left: n * this.$refs.g.clientWidth, behavior: 'smooth' }) } }">
        <div x-ref="g" @scroll.debounce.50ms="i = Math.round($refs.g.scrollLeft / $refs.g.clientWidth)" class="sf-gallery relative" style="border-radius:0; background:#fafaf9">
            @forelse ($product->images as $img)<img src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full aspect-square object-contain p-6 sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = {{ \Illuminate\Support\Js::from($img->url()) }}; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}" @if (! $loop->first) loading="lazy" @endif>@empty
                <div class="aspect-square flex items-center justify-center text-gray-400 text-xs">{{ __('store.no_image') }}</div>
            @endforelse
            @unless ($available)<span class="absolute inset-0 bg-white/80 flex items-center justify-center text-gray-600 text-sm z-10">{{ __('store.out') }}</span>@endunless
            <template x-if="variant && variant.image"><img :src="variant.image" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-contain p-6 sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = variant.image; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}"></template>
            <button type="button" class="sf-wish-btn" data-wish-id="{{ $product->id }}" aria-label="{{ __('store.wishlist_add') }}" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
        </div>
        @if ($product->images->count() > 1)
            <div class="mt-2 flex justify-center gap-1">@foreach ($product->images as $k => $img)<button type="button" @click="go({{ $k }})" class="w-1.5 h-1.5 rounded-full" :class="i === {{ $k }} ? 'bg-gray-700' : 'bg-gray-300'" aria-label="{{ $k + 1 }}"></button>@endforeach</div>
        @endif
    </div>

    <div class="md:pt-2">
        <h1 class="text-lg font-medium text-gray-900">{{ $product->name }}</h1>
        <p class="mt-2 flex items-center gap-2 flex-wrap"><span class="text-gray-900 text-base" x-text="priceText">{{ $price }}</span>
            <template x-if="pct > 0"><s class="text-gray-400 text-sm" x-text="oldPriceText"></s></template>
        </p>

        @if ($product->variants->isEmpty())
            <p class="mt-2 text-xs {{ $product->stock > 0 ? 'text-gray-500' : 'text-red-500' }}">
                @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
        @else
            <p class="mt-2 text-xs" :class="stock > 0 ? 'text-gray-500' : 'text-red-500'" x-text="stock > 0 ? {{ \Illuminate\Support\Js::from(__('store.in_stock')) }} : {{ \Illuminate\Support\Js::from(__('store.out')) }}"></p>
            <div class="mt-5 space-y-4">
                @foreach ($optionChoices as $name => $values)
                    <div><p class="text-xs text-gray-400 mb-1.5">{{ $name }}</p>
                        <select class="border border-gray-300 rounded-none text-sm px-2 py-1.5 w-full max-w-[12rem]" @change="sel[{{ \Illuminate\Support\Js::from($name) }}] = $event.target.value">
                            <option value="">—</option>
                            @foreach ($values as $val)<option value="{{ $val }}">{{ $val }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
                <p class="text-xs text-amber-600" x-show="! ready">{{ __('store.choose_options', ['names' => $optionChoices->keys()->implode(' / ')]) }}</p>
            </div>
        @endif
        <template x-if="variant && variant.sku"><p class="text-xs text-gray-400 mt-1">{{ __('store.sku') }}: <span x-text="variant.sku"></span></p></template>

        @if ($available)
            <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-6 flex items-center gap-2">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" :value="variant?.id ?? ''">
                <div class="flex items-center border border-gray-300 shrink-0">
                    <button type="button" class="w-9 h-9 flex items-center justify-center text-sm" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                    <input type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-10 border-0 text-center text-sm focus:ring-0 p-0">
                    <button type="button" class="w-9 h-9 flex items-center justify-center text-sm" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                </div>
                <button class="flex-1 h-9 bg-gray-900 text-white text-sm hover:bg-gray-700 transition-colors" :disabled="! ready || stock < 1" :class="(! ready || stock < 1) ? 'opacity-40 cursor-not-allowed' : ''">{{ __('store.add_to_cart') }}</button>
            </form>
        @endif
        @if ($wa)<a class="mt-2 inline-block text-xs underline text-gray-500" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ $available ? __('store.order_wa') : __('store.ask_wa') }}</a>@endif

        <div class="mt-8 pt-6 border-t border-gray-100 text-xs text-gray-500 space-y-1.5">
            <p>{{ __('store.delivery') }}:
                @if ($charge <= 0){{ __('store.free_delivery') }}
                @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif</p>
            @if ($methods->count())<p>{{ __('store.pay') }}: {{ $methods->map(fn ($m) => __('store.'.$m))->implode(', ') }}</p>@endif
            @if ($deliveryEstimate)<p>{{ $deliveryEstimate }}</p>@endif
        </div>

        @if ($product->description)<div class="mt-6 pt-6 border-t border-gray-100"><p class="whitespace-pre-line text-gray-600 text-sm leading-relaxed">{{ $product->description }}</p></div>@endif
        @include('storefront.product-templates._specs')
        @include('storefront.product-templates._reviews')
        @include('storefront.product-templates._qna')
        @include('storefront.product-templates._bundle')

        <div class="mt-6 flex items-center flex-wrap gap-3 text-xs text-gray-500" x-data="{ ok: false }" aria-live="polite">
            <a class="underline" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
            <a class="underline" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
            <button type="button" class="underline" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button>
        </div>
    </div>
</div>

@if ($related->count())
<section class="mt-16 mb-6"><h2 class="text-sm text-gray-500 mb-3">{{ __('store.related') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">@foreach ($related as $p)@include('storefront._card')@endforeach</div></section>
@endif
<div class="h-20 md:hidden"></div>

@if ($available || $wa)
<div class="sf-bar">
    <div class="min-w-0">
        @if ($available)
            <p class="text-lg leading-tight">{{ $price }}</p>
        @else
            <p class="text-sm text-red-600">{{ __('store.out') }}</p>
        @endif
    </div>
    @if ($available)<button class="flex-1 h-10 bg-gray-900 text-white text-sm" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>@endif
    @if ($wa)
        <a class="sf-btn sf-btn-wa {{ $available ? '!px-3' : 'flex-1' }}" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">
            @if ($available)<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>@else{{ __('store.ask_wa') }}@endif
        </a>
    @endif
</div>
@endif
