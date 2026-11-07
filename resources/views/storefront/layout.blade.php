@php $cartCount = (new \App\Support\Cart($store->id))->count(); $theme = $store->theme; $logo = $store->fileUrl($look['logo'] ?? null); @endphp
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $store->name)</title>
@yield('head')
@if ($logo)<link rel="icon" href="{{ $logo }}">@endif
@vite(['resources/css/app.css'])
<style>
:root { --c: {{ $color }}; }
.sf { background:#f9fafb; color:#111827; font-family: ui-sans-serif, system-ui, sans-serif; }
.sf.font-serif-x, .sf.font-serif-x h1, .sf.font-serif-x h2 { font-family: Georgia, 'Times New Roman', serif; }
.sf-head { background: var(--c); color:#fff; }
.sf-card { background:#fff; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
.sf-pill { background: rgba(255,255,255,.2); border-radius:999px; }
.sf-chip { border:1px solid #d1d5db; border-radius:999px; padding:.25rem .9rem; font-size:.875rem; background:#fff; }
.sf-chip.on { background: var(--c); color:#fff; border-color: var(--c); }
.sf-hero { background: linear-gradient(135deg, var(--c), #111827); color:#fff; border-radius:16px; }
.sf-hero-btn { background:#fff; color:#111827; border-radius:8px; padding:.6rem 1.2rem; font-weight:600; display:inline-block; }
.sf-announce { background:#111827; color:#fff; font-size:.8rem; text-align:center; padding:.4rem 1rem; }
/* theme: bold */
.theme-bold { background:#0f172a; color:#e5e7eb; }
.theme-bold .sf-head { background:#020617; border-bottom:4px solid var(--c); }
.theme-bold .sf-card { background:#1e293b; box-shadow:none; border:1px solid #334155; border-radius:4px; color:#e5e7eb; }
.theme-bold .sf-chip { background:#1e293b; border-color:#475569; color:#e5e7eb; }
.theme-bold h1, .theme-bold h2 { text-transform:uppercase; letter-spacing:.04em; }
.theme-bold .text-gray-500, .theme-bold .text-gray-600 { color:#94a3b8; }
.theme-bold .bg-white { background:#1e293b; color:#e5e7eb; }
.theme-bold .bg-gray-100 { background:#334155; }
.theme-bold input, .theme-bold textarea, .theme-bold select { color:#111827; }
.theme-bold .sf-hero { border-radius:4px; }
/* theme: boutique */
.theme-boutique { background:#faf6f2; color:#44403c; }
.theme-boutique .sf-head { background:#fff; color:#44403c; border-bottom:1px solid #e7e0d8; }
.theme-boutique .sf-head a { color: var(--c); }
.theme-boutique .sf-pill { background:#f5ebe4; }
.theme-boutique .sf-card { border-radius:2px; box-shadow:none; border:1px solid #e7e0d8; }
.theme-boutique h1, .theme-boutique h2 { font-family: Georgia, serif; font-weight:500; }
.theme-boutique .sf-hero { background: linear-gradient(135deg, var(--c), #78716c); border-radius:2px; }
</style></head>
<body class="sf theme-{{ $theme }} {{ ($look['font'] ?? 'sans') === 'serif' ? 'font-serif-x' : '' }} antialiased">
@if (! empty($look['announcement']))<div class="sf-announce">{{ $look['announcement'] }}</div>@endif
<header class="sf-head"><div class="max-w-5xl mx-auto px-5 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-3 text-xl font-bold">@if ($logo)<img src="{{ $logo }}" alt="{{ $store->name }}" class="h-10 w-auto max-w-[140px] object-contain">@endif<span>{{ $store->name }}</span></a>
    <a href="/cart" class="sf-pill text-sm px-4 py-1">Cart ({{ $cartCount }})</a></div></header>
<main class="max-w-5xl mx-auto px-5 py-8">
    @if (session('status'))<div class="mb-4 bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
    @yield('content')
</main>
<footer class="max-w-5xl mx-auto px-5 pb-10 text-xs text-gray-500">&copy; {{ date('Y') }} {{ $store->name }}</footer>
</body></html>
