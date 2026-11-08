@php
    $banner = $store->fileUrl($look['banner'] ?? null); $title = $look['hero_title'] ?: $store->name;
    $hasProducts = in_array('products', $store->sections(), true);
    $theme = $store->theme;
    $overlayMap = ['dark' => 'rgba(0,0,0,.45)', 'soft' => 'rgba(0,0,0,.18)', 'none' => 'rgba(0,0,0,0)'];
    $ov = $overlayMap[$look['overlay'] ?? 'dark'] ?? $overlayMap['dark'];
@endphp
@if ($theme === 'boutique')
<section class="mb-8">
    @if ($banner)<div class="sf-hero-boutique-img" style="background-image:url('{{ $banner }}')"></div>@endif
    <div class="sf-hero-boutique-card {{ $banner ? 'has-banner' : '' }}">
        <h1 class="text-2xl md:text-4xl leading-tight">{{ $title }}</h1>
        @if ($look['hero_text'])<p class="mt-2 text-base md:text-lg opacity-80">{{ $look['hero_text'] }}</p>@endif
        @if ($look['hero_button'] && $hasProducts)<a href="#products" class="sf-hero-btn mt-4">{{ $look['hero_button'] }}</a>@endif
    </div>
</section>
@elseif ($theme === 'mono')
<section class="mb-8 text-center">
    @if ($banner)<div class="sf-hero-mono-img" style="background-image:url('{{ $banner }}')"></div>@endif
    <div class="sf-hero-mono-text">
        <span class="sf-hero-mono-kicker">{{ $store->name }}</span>
        <h1 class="text-2xl md:text-5xl font-semibold tracking-tight leading-tight">{{ $title }}</h1>
        @if ($look['hero_text'])<p class="mt-3 text-base md:text-lg text-gray-600">{{ $look['hero_text'] }}</p>@endif
        @if ($look['hero_button'] && $hasProducts)<a href="#products" class="sf-hero-btn mt-5 inline-flex">{{ $look['hero_button'] }}</a>@endif
    </div>
</section>
@elseif ($theme === 'bold')
<section class="mb-6">
    <div class="sf-hero-bold" @if ($banner) style="background-image:url('{{ $banner }}'); --hero-ov: {{ $ov }}" @endif>
        <div class="sf-hero-bold-inner">
            <span class="sf-hero-bold-rule"></span>
            <h1 class="text-2xl md:text-4xl font-bold uppercase tracking-wide leading-tight">{{ $title }}</h1>
            @if ($look['hero_text'])<p class="mt-2 text-base md:text-lg opacity-90">{{ $look['hero_text'] }}</p>@endif
            @if ($look['hero_button'] && $hasProducts)<a href="#products" class="sf-hero-btn mt-4">{{ $look['hero_button'] }}</a>@endif
        </div>
    </div>
</section>
@elseif ($theme === 'vibrant')
<section class="mb-6">
    <div class="sf-hero-vibrant overflow-hidden relative" @if ($banner) style="background: linear-gradient({{ $ov }}, {{ $ov }}), url('{{ $banner }}') center/cover" @endif>
        <div class="sf-hero-vibrant-inner">
            <span class="sf-hero-kicker">✦ {{ $store->name }}</span>
            <h1 class="text-2xl md:text-4xl font-extrabold leading-tight">{{ $title }}</h1>
            @if ($look['hero_text'])<p class="mt-2 text-base md:text-lg opacity-90">{{ $look['hero_text'] }}</p>@endif
            @if ($look['hero_button'] && $hasProducts)<a href="#products" class="sf-hero-btn mt-4">{{ $look['hero_button'] }}</a>@endif
        </div>
    </div>
</section>
@else
<section class="mb-5">
    <div class="sf-hero overflow-hidden relative" @if ($banner) style="background: linear-gradient({{ $ov }}, {{ $ov }}), url('{{ $banner }}') center/cover" @endif>
        <div class="px-5 py-7 md:py-12 max-w-xl">
            <h1 class="text-2xl md:text-4xl font-bold leading-tight">{{ $title }}</h1>
            @if ($look['hero_text'])<p class="mt-2 text-base md:text-lg opacity-90">{{ $look['hero_text'] }}</p>@endif
            @if ($look['hero_button'] && $hasProducts)<a href="#products" class="sf-hero-btn mt-4">{{ $look['hero_button'] }}</a>@endif
        </div>
    </div>
</section>
@endif
