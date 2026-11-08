<nav class="text-sm text-gray-500 mb-3"><a href="{{ $store->storePath('/') }}" class="underline">{{ $store->name }}</a>@if ($product->category) / <a href="{{ $store->storePath('/') }}?c={{ $product->category->slug }}" class="underline">{{ $product->category->name }}</a>@endif</nav>
<div class="grid md:grid-cols-2 gap-8 md:gap-12" x-data="{
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
    {{-- image floats on a soft brand-colour block, rounded everywhere - beauty brands keep it warm and friendly --}}
    <div class="rounded-[2rem] p-6 md:p-10" style="background: var(--c-soft)" x-data="{ i: 0, go(n) { this.$refs.g.scrollTo({ left: n * this.$refs.g.clientWidth, behavior: 'smooth' }) } }">
        <div x-ref="g" @scroll.debounce.50ms="i = Math.round($refs.g.scrollLeft / $refs.g.clientWidth)" class="flex overflow-x-auto scroll-snap-x relative rounded-2xl" style="scroll-snap-type:x mandatory; scrollbar-width:none;">
            @forelse ($product->images as $img)<img src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-2xl shrink-0" style="scroll-snap-align:center" @if (! $loop->first) loading="lazy" @endif>@empty
                <div class="w-full aspect-square bg-white/60 rounded-2xl flex items-center justify-center text-gray-400 text-xs">{{ __('store.no_image') }}</div>
            @endforelse
        </div>
        @unless ($available)<p class="mt-3 text-center font-semibold text-gray-700">{{ __('store.out') }}</p>@endunless
        @if ($product->images->count() > 1)
            <div class="mt-4 flex justify-center gap-2">@foreach ($product->images as $k => $img)<button type="button" @click="go({{ $k }})" class="w-10 h-10 rounded-full overflow-hidden border-2" :class="i === {{ $k }} ? 'border-[var(--c)]' : 'border-transparent opacity-70'" aria-label="{{ $k + 1 }}"><img src="{{ $img->url() }}" alt="" class="w-full h-full object-cover"></button>@endforeach</div>
        @endif
    </div>

    <div>
        <h1 class="text-2xl font-semibold leading-snug">{{ $product->name }}</h1>
        <p class="mt-3 flex items-center gap-3 flex-wrap"><span class="sf-price text-2xl" x-text="priceText">{{ $price }}</span>
            <template x-if="pct > 0"><s class="text-gray-400" x-text="oldPriceText"></s></template>
            <template x-if="pct > 0"><span class="sf-tag" x-text="'-' + pct + '%'" style="border-radius:999px"></span></template>
            @if ($pct <= 0 && $isNew)<span class="sf-tag-new" style="border-radius:999px" x-show="! variant">{{ __('store.new') }}</span>@endif
        </p>

        @if ($product->variants->isEmpty())
            <p class="mt-2 text-sm font-medium {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">
                @if ($product->stock < 1){{ __('store.out') }}@elseif ($product->stock <= 3){{ __('store.only_left', ['n' => \App\Support\Fmt::digits($product->stock)]) }}@else{{ __('store.in_stock') }}@endif</p>
        @else
            <p class="mt-2 text-sm font-medium" :class="stock > 0 ? 'text-green-700' : 'text-red-600'" x-text="stock > 0 ? {{ \Illuminate\Support\Js::from(__('store.in_stock')) }} : {{ \Illuminate\Support\Js::from(__('store.out')) }}"></p>

            <div class="mt-4 space-y-4">
                @foreach ($optionChoices as $name => $values)
                    @php $isColorish = str_contains(strtolower($name), 'color') || str_contains(strtolower($name), 'colour'); @endphp
                    <div><p class="text-sm font-medium mb-2">{{ $name }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($values as $val)
                                {{-- only trust single-word/hex values as a literal CSS color - "Rose Gold" etc. fall back to a text chip --}}
                                @php $swatch = $isColorish && preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]+$/', $val) ? strtolower($val) : null; @endphp
                                @if ($swatch)
                                    <button type="button" class="w-9 h-9 rounded-full border-2" :class="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'border-gray-900' : 'border-gray-200'" style="background: {{ $swatch }}" @click="sel[{{ \Illuminate\Support\Js::from($name) }}] = {{ \Illuminate\Support\Js::from($val) }}" title="{{ $val }}"></button>
                                @else
                                    <button type="button" class="sf-chip" style="border-radius:999px" :class="sel[{{ \Illuminate\Support\Js::from($name) }}] === {{ \Illuminate\Support\Js::from($val) }} ? 'on' : ''" @click="sel[{{ \Illuminate\Support\Js::from($name) }}] = {{ \Illuminate\Support\Js::from($val) }}">{{ $val }}</button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="text-sm text-amber-600" x-show="! ready">{{ __('store.choose_options', ['names' => $optionChoices->keys()->implode(' / ')]) }}</p>
            </div>
        @endif

        @if ($available)
            <form id="addform" method="POST" action="{{ $store->storePath('/cart/add') }}" class="mt-6 flex items-center gap-3">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" :value="variant?.id ?? ''">
                <div class="flex items-center border border-gray-200 rounded-full shrink-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.max(1, qty - 1)" aria-label="-1">−</button>
                    <input type="number" name="qty" x-model.number="qty" min="1" :max="max" class="w-12 border-0 text-center focus:ring-0 p-0">
                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg leading-none" @click="qty = Math.min(max, qty + 1)" aria-label="+1">+</button>
                </div>
                <button class="sf-btn flex-1 rounded-full font-semibold" :disabled="! ready || stock < 1" :class="(! ready || stock < 1) ? 'opacity-50 cursor-not-allowed' : ''">{{ __('store.add_to_cart') }}</button>
            </form>
        @endif
        @if ($wa)<a class="sf-btn sf-btn-wa mt-3 w-full rounded-full font-semibold" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">{{ $available ? __('store.order_wa') : __('store.ask_wa') }}</a>@endif

        <div class="mt-5 rounded-2xl p-4 text-sm space-y-3" style="background: var(--c-soft)">
            <p><span class="font-semibold">{{ __('store.delivery') }}:</span>
                @if ($charge <= 0){{ __('store.free_delivery') }}
                @else{{ __('store.delivery_charge', ['amount' => \App\Support\Fmt::money($charge)]) }}@if ($free > 0) · {{ __('store.free_over', ['amount' => \App\Support\Fmt::money($free)]) }}@endif @endif</p>
            @if ($methods->count())<p class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ __('store.pay') }}:</span>
                @foreach ($methods as $m)<span class="sf-trust-chip">{{ __('store.'.$m) }}</span>@endforeach</p>@endif
        </div>

        @if ($product->description)<div class="mt-5"><h2 class="font-semibold mb-1">{{ __('store.description') }}</h2><p class="whitespace-pre-line text-gray-700">{{ $product->description }}</p></div>@endif

        <div class="mt-6 flex items-center flex-wrap gap-2 text-sm" x-data="{ ok: false }" aria-live="polite"><span class="font-semibold mr-1">{{ __('store.share') }}:</span>
            <a class="sf-chip" style="border-radius:999px" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}">Facebook</a>
            <a class="sf-chip" style="border-radius:999px" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($product->name.' '.$url) }}">WhatsApp</a>
            <button type="button" class="sf-chip" style="border-radius:999px" @click="navigator.clipboard.writeText('{{ $url }}').then(() => { ok = true; setTimeout(() => ok = false, 2000) })" x-text="ok ? '{{ __('store.copied') }}' : '{{ __('store.copy_link') }}'"></button></div>
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
    @if ($available)<button class="sf-btn flex-1 rounded-full" type="submit" form="addform">{{ __('store.add_to_cart') }}</button>@endif
    @if ($wa)
        <a class="sf-btn sf-btn-wa {{ $available ? '!px-3' : 'flex-1' }} rounded-full" aria-label="WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($available ? $waText : $waTextOos) }}" target="_blank" rel="noopener">
            @if ($available)<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>@else{{ __('store.ask_wa') }}@endif
        </a>
    @endif
</div>
@endif
