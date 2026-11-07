@php $banner = $store->fileUrl($look['banner'] ?? null); $title = $look['hero_title'] ?: $store->name; @endphp
<section class="mb-8">
    <div class="sf-hero overflow-hidden relative" @if ($banner) style="background: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $banner }}') center/cover" @endif>
        <div class="px-6 py-14 md:py-20 max-w-xl">
            <h1 class="text-3xl md:text-4xl font-bold">{{ $title }}</h1>
            @if ($look['hero_text'])<p class="mt-3 text-lg opacity-90">{{ $look['hero_text'] }}</p>@endif
            @if ($look['hero_button'])<a href="#products" class="sf-hero-btn mt-6">{{ $look['hero_button'] }}</a>@endif
        </div>
    </div>
</section>
