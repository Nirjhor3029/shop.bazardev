@php $banner = $store->fileUrl($look['banner'] ?? null); $title = $look['hero_title'] ?: $store->name; @endphp
<section class="mb-5">
    <div class="sf-hero overflow-hidden relative" @if ($banner) style="background: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $banner }}') center/cover" @endif>
        <div class="px-5 py-7 md:py-12 max-w-xl">
            <h1 class="text-2xl md:text-4xl font-bold leading-tight">{{ $title }}</h1>
            @if ($look['hero_text'])<p class="mt-2 text-base md:text-lg opacity-90">{{ $look['hero_text'] }}</p>@endif
            @if ($look['hero_button'])<a href="#products" class="sf-hero-btn mt-4">{{ $look['hero_button'] }}</a>@endif
        </div>
    </div>
</section>
