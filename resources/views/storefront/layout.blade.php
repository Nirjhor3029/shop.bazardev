@php
    $cartCount = (new \App\Support\Cart($store->id))->count();
    $theme = $store->theme;
    $logo = $store->fileUrl($look['logo'] ?? null);
    $wa = \App\Support\Fmt::whatsapp($look['whatsapp'] ?? null);
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $store->name)</title>
@hasSection('og')@yield('og')@else
<meta property="og:type" content="website"><meta property="og:site_name" content="{{ $store->name }}"><meta property="og:title" content="{{ $store->name }}">
<meta property="og:url" content="{{ $store->url() }}">
@if ($store->fileUrl($look['banner'] ?? null) ?: $logo)<meta property="og:image" content="{{ $store->fileUrl($look['banner'] ?? null) ?: $logo }}">@endif
@if (! empty($look['hero_text']))<meta property="og:description" content="{{ $look['hero_text'] }}">@endif
@endif
@yield('head')
@if ($logo)<link rel="icon" href="{{ $logo }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
:root {
    --c: {{ $color }};
    --c-soft: color-mix(in srgb, var(--c) 12%, white);
    --c-dark: color-mix(in srgb, var(--c) 82%, black);
    --c-ink: color-mix(in srgb, var(--c) 28%, #111827);
}
.sf { background:#f9fafb; color:#111827; font-family: 'Hind Siliguri', ui-sans-serif, system-ui, sans-serif; font-feature-settings: 'tnum'; }
.sf.font-serif-x, .sf.font-serif-x h1, .sf.font-serif-x h2 { font-family: Georgia, 'Noto Serif Bengali', 'Times New Roman', serif; }
.sf-head { background: var(--c); color:#fff; position: sticky; top: 0; z-index: 30; }
.sf-card { background:#fff; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
a.sf-card { transition: transform .15s ease, box-shadow .15s ease; }
a.sf-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,.1); }
.sf-pill { background: rgba(255,255,255,.2); border-radius:999px; }
.sf-chip { border:1px solid #d1d5db; border-radius:999px; padding:.3rem 1rem; font-size:.9rem; background:#fff; white-space:nowrap; min-height:44px; display:inline-flex; align-items:center; transition: background-color .15s ease, color .15s ease; }
.sf-chip.on { background: var(--c); color:#fff; border-color: var(--c); }
.sf-hero { background: linear-gradient(135deg, var(--c), var(--c-ink)); color:#fff; border-radius:16px; }
.sf-hero-btn { background:#fff; color:#111827; border-radius:8px; padding:.55rem 1.1rem; font-weight:600; display:inline-flex; align-items:center; min-height:44px; transition: transform .15s ease; }
.sf-hero-btn:hover { transform: translateY(-1px); }
.sf-announce { background: var(--c-ink); color:#fff; font-size:.8rem; text-align:center; padding:.4rem 1rem; }
.sf-btn { background: var(--c); color:#fff; border-radius:10px; padding:.7rem 1.4rem; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:.5rem; transition: background-color .15s ease; }
.sf-btn:hover { background: var(--c-dark); }
.sf-btn-wa { background:#16a34a; }
.sf-price { font-weight:700; color: var(--c); }
.sf-tag { display:inline-block; background:#fef2f2; color:#b91c1c; font-weight:700; font-size:.75rem; border-radius:6px; padding:.1rem .45rem; }
.sf-tag-new { display:inline-block; background:#ecfdf5; color:#047857; font-weight:700; font-size:.75rem; border-radius:6px; padding:.1rem .45rem; }
.sf-trust-chip { display:inline-flex; align-items:center; background: var(--c-soft); color: var(--c-dark); border-radius:999px; padding:.35rem .9rem; font-size:.78rem; font-weight:600; }
.sf-chips-row { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.25rem; scrollbar-width:none; }
.sf-chips-row::-webkit-scrollbar { display:none; }
.sf-gallery { display:flex; overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; border-radius:12px; background:#fff; }
.sf-gallery::-webkit-scrollbar { display:none; }
.sf-gallery > * { flex:0 0 100%; scroll-snap-align:center; }
.sf-fab { position:fixed; right:1rem; bottom:5.2rem; z-index:40; background:#16a34a; color:#fff; width:52px; height:52px; border-radius:999px; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px rgba(0,0,0,.25); }
@media (min-width: 768px) { .sf-fab { bottom:1.5rem; } }
.sf-bar { position:fixed; left:0; right:0; bottom:0; z-index:35; background:#fff; border-top:1px solid #e5e7eb; padding:.6rem 1rem calc(.6rem + env(safe-area-inset-bottom)); display:flex; align-items:center; gap:.75rem; }
@media (min-width: 768px) { .sf-bar { display:none; } }
.sf-reveal { opacity:0; transform:translateY(12px); transition: opacity .5s ease, transform .5s ease; }
.sf-reveal.is-visible { opacity:1; transform:none; }
@media (prefers-reduced-motion: reduce) { * { scroll-behavior:auto !important; transition:none !important; } .sf-reveal { opacity:1; transform:none; } }
@media (scripting: none) { .sf-reveal { opacity:1; transform:none; } }
/* theme: bold — dense, high-contrast, poster-like */
.theme-bold { background:#0f172a; color:#e5e7eb; }
.theme-bold main { padding-top:.75rem; padding-bottom:.75rem; }
.theme-bold .grid { gap:.5rem; }
.theme-bold .sf-head { background:#020617; border-bottom:4px solid var(--c); }
.theme-bold .sf-card, .theme-bold .sf-bar { background:#1e293b; box-shadow:none; border:1px solid #334155; border-radius:4px; color:#e5e7eb; }
.theme-bold a.sf-card:hover { box-shadow: 0 10px 24px rgba(0,0,0,.4); border-color: var(--c); }
.theme-bold .sf-chip { background:#1e293b; border-color:#475569; color:#e5e7eb; text-transform:uppercase; font-size:.72rem; letter-spacing:.03em; }
.theme-bold .sf-chip.on { background: var(--c); color:#fff; border-color: var(--c); }
.theme-bold h1, .theme-bold h2 { letter-spacing:.02em; }
.theme-bold .text-gray-500, .theme-bold .text-gray-600 { color:#94a3b8; }
.theme-bold .bg-white { background:#1e293b; color:#e5e7eb; }
.theme-bold .bg-gray-100 { background:#334155; }
.theme-bold input, .theme-bold textarea, .theme-bold select { color:#111827; }
.theme-bold .sf-gallery { background:#1e293b; }
.theme-bold .sf-trust-chip { background:#1e293b; color:#e5e7eb; border:1px solid #334155; }
/* bold hero: full-bleed image, dark scrim, colour accent rule */
.sf-hero-bold { position:relative; background: linear-gradient(135deg, var(--c), var(--c-ink)) center/cover no-repeat; border-radius:4px; overflow:hidden; }
.sf-hero-bold::before { content:''; position:absolute; inset:0; background:rgba(2,6,23,.45); }
.sf-hero-bold-inner { position:relative; padding:2.25rem 1.5rem; max-width:34rem; color:#fff; }
.sf-hero-bold-rule { display:block; width:2.5rem; height:4px; background:#fff; margin-bottom:.9rem; }
@media (min-width:768px) { .sf-hero-bold-inner { padding:3.5rem 2.5rem; } }
/* theme: boutique — airy, editorial, soft serif */
.theme-boutique { background:#faf6f2; color:#44403c; }
.theme-boutique main { padding-top:2rem; padding-bottom:2rem; }
.theme-boutique .grid { gap:1rem; }
.theme-boutique section + section, .theme-boutique .sf-reveal + .sf-reveal { margin-top:.5rem; }
.theme-boutique .sf-head { background:#fff; color:#44403c; border-bottom:1px solid #e7e0d8; }
.theme-boutique .sf-head a { color: var(--c); }
.theme-boutique .sf-pill { background:#f5ebe4; }
.theme-boutique .sf-card { border-radius:2px; box-shadow:none; border:1px solid #e7e0d8; }
.theme-boutique a.sf-card:hover { box-shadow: 0 10px 24px rgba(0,0,0,.06); border-color: var(--c); }
.theme-boutique h1, .theme-boutique h2 { font-family: Georgia, 'Noto Serif Bengali', serif; font-weight:500; }
.theme-boutique .sf-trust-chip { background:#fff; border:1px solid #e7e0d8; color:#57534e; }
/* boutique hero: plain image + overlapping card, no dark overlay */
.sf-hero-boutique-img { aspect-ratio:16/7; background-size:cover; background-position:center; border-radius:2px; }
.sf-hero-boutique-card { background:#fff; margin:0 0; padding:1.5rem; border-radius:2px; box-shadow:0 8px 24px rgba(0,0,0,.06); max-width:30rem; }
.sf-hero-boutique-card.has-banner { margin-top:-2.5rem; margin-inline:1.25rem; }
@media (min-width:768px) { .sf-hero-boutique-card { padding:2rem 2.5rem; } .sf-hero-boutique-card.has-banner { margin-top:-3rem; } }
</style></head>
<body class="sf theme-{{ $theme }} {{ ($look['font'] ?? 'sans') === 'serif' ? 'font-serif-x' : '' }} antialiased">
@if (! empty($look['announcement']))<div class="sf-announce">{{ $look['announcement'] }}</div>@endif
<header class="sf-head"><div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
    <a href="/" class="flex items-center gap-3 text-lg font-bold min-w-0">@if ($logo)<img src="{{ $logo }}" alt="" class="h-9 w-auto max-w-[120px] object-contain">@endif<span class="truncate">{{ $store->name }}</span></a>
    <a href="/cart" class="sf-pill text-sm px-3 py-1.5 flex items-center gap-1.5 shrink-0" aria-label="{{ __('store.cart') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        <span>{{ __('store.cart') }}</span><span class="font-bold">{{ \App\Support\Fmt::digits($cartCount) }}</span></a></div></header>
<main class="max-w-5xl mx-auto px-4 py-6">
    @if (session('status'))<div class="mb-4 bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
    @yield('content')
</main>
<footer class="max-w-5xl mx-auto px-4 pb-24 md:pb-10 text-xs text-gray-500">&copy; {{ \App\Support\Fmt::digits(date('Y')) }} {{ $store->name }} · {{ __('store.made_with') }}</footer>
@if ($wa && ! $__env->hasSection('hide_fab'))<a class="sf-fab" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="WhatsApp"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></a>@endif
@yield('bar')
<noscript><style>.sf-reveal{opacity:1!important;transform:none!important;}</style></noscript>
<script>
(function () {
    try {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var els = document.querySelectorAll('.sf-reveal');
        if (!('IntersectionObserver' in window) || !els.length) { els.forEach(function (el) { el.classList.add('is-visible'); }); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { entry.target.classList.add('is-visible'); io.unobserve(entry.target); }
            });
        }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
        els.forEach(function (el) { io.observe(el); });
    } catch (e) {
        document.querySelectorAll('.sf-reveal').forEach(function (el) { el.classList.add('is-visible'); });
    }
})();
</script>
</body></html>
