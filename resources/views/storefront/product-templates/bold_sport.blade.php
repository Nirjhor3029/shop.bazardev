<nav class="text-sm text-gray-500 mb-3"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
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
    {{-- big, full-width, high-energy image up top - sportswear sites lead with action, not a boxed gallery --}}
    <div class="relative rounded-2xl overflow-hidden" style="background: var(--c-ink)">
        @if ($product->images->isNotEmpty())
            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="w-full aspect-[16/10] object-cover sf-zoomable" @click="$store.zoom.open = true; $store.zoom.src = {{ \Illuminate\Support\Js::from($product->images->first()->url()) }}; $store.zoom.alt = {{ \Illuminate\Support\Js::from($product->name) }}">
        @else
            <div class="w-full aspect-[16/10] flex items-center justify-center text-white/50">{{ __('store.no_image') }}</div>
        @endif
        <template x-if="pct > 0"><span class="sf-ribbon" style="background: var(--c)" x-text="'-' + pct + '%'"></span></template>
        @unless ($available)<span class="absolute inset-0 bg-black/60 flex items-center justify-center font-bold text-white uppercase tracking-wide">{{ __('store.out') }}</span>@endunless
    </div>
    @if ($product->images->count() > 1)
        <div class="mt-3 flex gap-2 overflow-x-auto">@foreach ($product->images as $img)<img src="{{ $img->url() }}" alt="" class="w-16 h-16 rounded-lg object-cover shrink-0">@endforeach</div>
    @endif

    <h1 class="mt-6 text-3xl md:text-4xl font-extrabold uppercase tracking-tight leading-tight">{{ $product->name }}</h1>

    <div class="mt-4 rounded-2xl p-5" style="background: var(--c-soft)">
        <p class="flex items-center gap-3 flex-wrap"><span class="sf-price text-3xl font-extrabold" x-text="priceText">{{ $price }}</span>
            <template x-if="pct > 0"><s class="text-gray-400 text-lg" x-text="oldPriceText"></s></template>
        </p>

        @if ($product->variants->isEmpty())
            <p class="mt-1 text-sm font-bold uppercase tracking-wide {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
                @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
        @else
            <p class="mt-1 text-sm font-bold uppercase tracking-wide" :class="stock > 0 ? 'text-green-700' : 'text-red-600'" x-text="stock > 0 ? {{ \Illuminate\Support\Js::from(__('store.in_stock')) }} : {{ \Illuminate\Support\Js::from(__('store.out')) }}"></p>
            <div class="mt-4 space-y-3">
                @foreach ($optionChoices as $name => $values)
                    <div><p class="text-xs font-bold uppercase tracking-wide mb-2">{{ $name }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($values as $val)
                                <button type="button" class="px-4 py-2 rounded-lg font-bold text-sm uppercase border-2" :class="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'text-white border-transparent' : 'bg-white border-gray-200'" :style="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'background: var(--c)' : ''" @click="sel[{{ \Illuminate\Support\Js::from($name) }}] = {{ \Illuminate\Support\Js::from($val) }}">{{ $val }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="text-sm text-amber-700 font-medium" x-show="! ready">{{ __('store.choose_options', ['names' => $optionChoices->keys()->implode(' / ')]) }}</p>
            </div>
        @endif
        <template x-if="variant && variant.sku"><p class="mt-1 text-xs text-gray-400">{{ __('store.sku') }}: <span x-text="variant.sku"></span></p></template>

        @if ($available)
            <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-5 flex items-center gap-3">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" :value="variant?.id ?? ''">
                <div class="flex items-center bg-white border-2 border-gray-200 rounded-lg shrink-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg font-bold leading-none" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                    <input type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-12 border-0 text-center font-bold focus:ring-0 p-0 bg-transparent">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg font-bold leading-none" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                </div>
                <button class="sf-btn flex-1 font-extrabold uppercase tracking-wide" :disabled="! ready || stock < 1" :class="(! ready || stock < 1) ? 'opacity-50 cursor-not-allowed' : ''">{{ __('store.add_to_cart') }}</button>
            </form>
        @endif
        @if ($wa)<a class="sf-btn sf-btn-wa mt-3 w-full font-bold uppercase tracking-wide" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ $available ? __('store.order_wa') : __('store.ask_wa') }}</a>@endif
    </div>

    <div class="mt-5 sf-card p-4 text-sm space-y-3">
        <p><span class="font-bold">{{ __('store.delivery') }}:</span>
            @if ($charge <= 0){{ __('store.free_delivery') }}
            @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif
            @if ($deliveryEstimate) · {{ $deliveryEstimate }}@endif</p>
        @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-bold">{{ __('store.pay') }}:</span>
            @foreach ($methods as $m)<span class="sf-chip !py-0.5 !text-xs">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
    </div>

    @if ($product->description)<div class="mt-5"><h2 class="font-bold uppercase text-sm tracking-wide mb-1">{{ __('store.description') }}</h2><p class="whitespace-pre-line text-gray-700">{{ $product->description }}</p></div>@endif
    @include('storefront.product-templates._specs')

    <div class="mt-6 flex items-center flex-wrap gap-2 text-sm" x-data="{ ok: false }" aria-live="polite"><span class="font-bold mr-1">{{ __('store.share') }}:</span>
        <a class="sf-chip" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
        <a class="sf-chip" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
        <button type="button" class="sf-chip" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button></div>
</div>

@if ($related->count())
<section class="mt-12 mb-6"><h2 class="text-xl font-extrabold uppercase tracking-tight mb-3">{{ __('store.related') }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">@foreach ($related as $p)@include('storefront._card')@endforeach</div></section>
@endif
<div class="h-20 md:hidden"></div>

@if ($available || $wa)
<div class="sf-bar">
    <div class="min-w-0">
        @if ($available)
            <p class="sf-price text-xl leading-tight font-extrabold">{{ $price }}</p>@if ($pct > 0)<s class="text-xs text-gray-400">{{ \App\Support\Fmt::money($product->price) }}</s>@endif
        @else
            <p class="text-sm font-semibold text-red-600">{{ __('store.out') }}</p>
        @endif
    </div>
    @if ($available)<button class="sf-btn flex-1 font-extrabold uppercase" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>@endif
    @if ($wa)
        <a class="sf-btn sf-btn-wa {{ $available ? '!px-3' : 'flex-1' }}" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">
            @if ($available)<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>@else{{ __('store.ask_wa') }}@endif
        </a>
    @endif
</div>
@endif
