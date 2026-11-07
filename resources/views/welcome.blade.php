@extends('layouts.market')
@section('content')
{{-- HERO --}}
<section class="bg-gradient-to-br from-brand-soft via-white to-white"><div class="max-w-6xl mx-auto px-4 py-12 md:py-20 grid md:grid-cols-2 gap-10 items-center">
    <div>
        <span class="inline-block bg-white border border-brand/30 text-brand text-xs font-semibold rounded-full px-3 py-1">{{ __('market.hero_badge') }}</span>
        <h1 class="mt-4 text-3xl md:text-5xl font-bold leading-tight">{{ __('market.hero_title') }}</h1>
        <p class="mt-4 text-lg text-gray-600">{{ __('market.hero_text') }}</p>
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="bg-brand hover:bg-brand-dark text-white font-semibold rounded-lg px-6 py-3 shadow-sm">{{ __('market.open_store') }}</a>
            @if (config('app.demo_mode'))<form method="POST" action="{{ route('demo.login') }}">@csrf<button class="bg-white border border-gray-300 hover:border-brand hover:text-brand font-semibold rounded-lg px-6 py-3">{{ __('market.demo') }}</button></form>@endif
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-2 text-xs"><span class="text-gray-500 mr-1">{{ __('market.pay_strip') }}:</span>
            <span class="px-2 py-1 rounded bg-[#e2136e] text-white font-semibold">bKash</span><span class="px-2 py-1 rounded bg-[#f6921e] text-white font-semibold">Nagad</span><span class="px-2 py-1 rounded bg-[#8c3494] text-white font-semibold">Rocket</span><span class="px-2 py-1 rounded bg-emerald-600 text-white font-semibold">{{ __('market.cod') }}</span></div>
    </div>
    {{-- store mock --}}
    <div class="relative" aria-hidden="true">
        <div class="rounded-2xl border bg-white shadow-xl overflow-hidden">
            <div class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 border-b"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                <span class="ml-3 text-[11px] text-gray-500 bg-white rounded px-2 py-0.5">yourname.shop.bazardev.com</span></div>
            <div class="bg-brand text-white px-4 py-3 text-sm font-semibold">Your Fashion House</div>
            <div class="p-4 space-y-3"><div class="rounded-lg bg-gradient-to-r from-brand to-ink text-white p-5"><p class="font-bold text-lg">Eid Collection</p><p class="text-xs opacity-80">Free delivery over ৳2000</p></div>
                <div class="grid grid-cols-3 gap-2">@foreach ([['#fda4af','৳1,500'],['#93c5fd','৳1,200'],['#fcd34d','৳2,700']] as [$c,$pr])<div class="rounded-lg border p-2"><div class="aspect-square rounded" style="background: {{ $c }}"></div><p class="mt-1.5 text-[11px] font-semibold">{{ $pr }}</p></div>@endforeach</div></div>
        </div>
        <div class="absolute -bottom-4 -left-4 bg-white border rounded-xl shadow-lg px-4 py-3 text-sm"><p class="font-semibold">🔔 {{ app()->getLocale() === 'bn' ? 'নতুন অর্ডার!' : 'New order!' }}</p><p class="text-xs text-gray-500">৳1,560 · bKash</p></div>
    </div>
</div></section>

{{-- 3 BENEFITS --}}
<section class="max-w-6xl mx-auto px-4 -mt-2 py-10 grid md:grid-cols-3 gap-5">
    @foreach ([['b1','⚡'],['b2','💸'],['b3','🎨']] as [$k,$ic])
        <div class="border rounded-2xl p-6 bg-white"><div class="w-11 h-11 rounded-xl bg-brand-soft flex items-center justify-center text-xl">{{ $ic }}</div>
            <h3 class="mt-4 font-semibold text-lg">{{ __("market.{$k}_t") }}</h3><p class="mt-1 text-gray-600 text-sm">{{ __("market.{$k}_d") }}</p></div>
    @endforeach
</section>

{{-- DEALS --}}
@if ($deals->count())
<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-end justify-between mb-4"><div><h2 class="text-2xl font-bold">🔥 {{ __('market.deals') }}</h2><p class="text-sm text-gray-500">{{ __('market.deals_sub') }}</p></div></div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">@foreach ($deals as $p)@include('market._card')@endforeach</div>
</section>
@endif

{{-- STORES --}}
@if ($stores->count())
<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-4"><h2 class="text-2xl font-bold">{{ __('market.stores') }}</h2><p class="text-sm text-gray-500">{{ __('market.stores_sub') }}</p></div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($stores as $s)
            @php $logo = $s->fileUrl($s->look()['logo'] ?? null); $c = $s->settings['color'] ?? '#f0502a'; @endphp
            <a href="{{ $s->url() }}" class="block border rounded-2xl p-4 bg-white hover:shadow-md transition">
                <div class="flex items-center gap-3"><div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold overflow-hidden shrink-0" style="background: {{ $c }}">@if ($logo)<img src="{{ $logo }}" alt="" class="w-full h-full object-contain bg-white">@else{{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}@endif</div>
                    <div class="min-w-0"><p class="font-semibold truncate">{{ $s->name }}</p><p class="text-xs text-gray-500">{{ __('market.products_n', ['n' => $counts[$s->id] ?? 0]) }}</p></div></div>
                <p class="mt-3 text-sm font-semibold text-brand">{{ __('market.visit') }} →</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- NEW --}}
@if ($new->count())
<section class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-4"><h2 class="text-2xl font-bold">{{ __('market.new') }}</h2><p class="text-sm text-gray-500">{{ __('market.new_sub') }}</p></div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">@foreach ($new as $p)@include('market._card')@endforeach</div>
</section>
@endif

{{-- HOW --}}
<section id="how" class="bg-gray-50 mt-8"><div class="max-w-6xl mx-auto px-4 py-14">
    <div class="text-center mb-10"><h2 class="text-2xl md:text-3xl font-bold">{{ __('market.how_t') }}</h2><p class="text-gray-500 mt-1">{{ __('market.how_sub') }}</p></div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach (['s1','s2','s3','s4'] as $i => $k)
            <div class="bg-white rounded-2xl p-6 border relative"><div class="w-9 h-9 rounded-full bg-brand text-white font-bold flex items-center justify-center">{{ $i + 1 }}</div>
                <h3 class="mt-4 font-semibold">{{ __("market.{$k}_t") }}</h3><p class="mt-1 text-sm text-gray-600">{{ __("market.{$k}_d") }}</p></div>
        @endforeach
    </div>
</div></section>

{{-- WHY --}}
<section class="max-w-6xl mx-auto px-4 py-14">
    <h2 class="text-2xl md:text-3xl font-bold text-center mb-8">{{ __('market.why_t') }}</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach (['w1','w2','w3','w4','w5','w6'] as $k)
            <div class="flex items-center gap-3 border rounded-xl p-4 bg-white"><span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">✓</span><span class="font-medium">{{ __("market.$k") }}</span></div>
        @endforeach
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="max-w-3xl mx-auto px-4 py-6">
    <h2 class="text-2xl md:text-3xl font-bold text-center mb-6">{{ __('market.faq_t') }}</h2>
    <div class="space-y-2">@foreach (['1','2','3','4','5','6'] as $n)
        <details class="group border rounded-xl bg-white px-4 py-3"><summary class="cursor-pointer font-semibold list-none flex items-center justify-between">{{ __("market.q$n") }}<span class="text-brand group-open:rotate-45 transition text-xl">+</span></summary>
            <p class="mt-2 text-gray-600 text-sm">{{ __("market.a$n") }}</p></details>
    @endforeach</div>
</section>

{{-- CTA --}}
<section class="max-w-6xl mx-auto px-4 mt-14"><div class="rounded-3xl bg-gradient-to-r from-brand to-brand-dark text-white p-8 md:p-12 text-center">
    <h2 class="text-2xl md:text-3xl font-bold">{{ __('market.cta_t') }}</h2><p class="mt-2 opacity-90">{{ __('market.cta_d') }}</p>
    <a href="{{ route('register') }}" class="inline-block mt-6 bg-white text-brand font-bold rounded-lg px-8 py-3 hover:bg-brand-soft">{{ __('market.open_store') }}</a></div></section>
@endsection
