@php $other = app()->getLocale() === 'bn' ? 'en' : 'bn'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('market.brand').' · '.__('market.hero_title'))</title>
    <meta name="description" content="@yield('desc', __('market.hero_text'))">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink bg-white antialiased">
<div class="bg-ink text-white text-xs"><div class="max-w-6xl mx-auto px-4 h-9 flex items-center justify-between">
    <a href="{{ route('register') }}" class="hover:underline">{{ __('market.sell_link') }}</a>
    <div class="flex items-center gap-4"><a href="#faq" class="hover:underline">{{ __('market.help') }}</a>
        <a href="{{ route('lang', $other) }}" class="border border-white/30 rounded px-2 py-0.5 hover:bg-white/10">{{ $other === 'en' ? 'English' : 'বাংলা' }}</a></div>
</div></div>
<header class="bg-white border-b sticky top-0 z-30"><div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-3 md:gap-6">
    <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0"><x-application-logo class="h-9 w-9" /><span class="font-bold text-lg hidden sm:inline">{{ __('market.brand') }}</span></a>
    <form action="{{ route('search') }}" method="GET" class="flex-1 flex">
        <input type="search" name="q" value="{{ request('q') }}" maxlength="60" placeholder="{{ __('market.search_ph') }}" class="w-full rounded-l-lg border-gray-300 border-r-0 text-sm" aria-label="{{ __('market.search_btn') }}">
        <button class="bg-brand hover:bg-brand-dark text-white px-4 md:px-6 rounded-r-lg text-sm font-semibold">{{ __('market.search_btn') }}</button>
    </form>
    <nav class="flex items-center gap-2 shrink-0">
        @auth <a href="{{ route('seller.dashboard') }}" class="text-sm font-semibold px-3 py-2 rounded-lg hover:bg-gray-100">{{ __('Dashboard') }}</a>
        @else <a href="{{ route('login') }}" class="text-sm font-semibold px-3 py-2 rounded-lg hover:bg-gray-100">{{ __('market.login') }}</a>
        <a href="{{ route('register') }}" class="hidden sm:inline-block text-sm font-semibold px-4 py-2 rounded-lg bg-brand text-white hover:bg-brand-dark">{{ __('market.open_store') }}</a> @endauth
    </nav>
</div></header>
<main>@yield('content')</main>
<footer class="bg-ink text-gray-300 mt-16"><div class="max-w-6xl mx-auto px-4 py-12 grid gap-8 md:grid-cols-4">
    <div><div class="flex items-center gap-2 text-white font-bold"><x-application-logo class="h-8 w-8" />{{ __('market.brand') }}</div>
        <p class="mt-3 text-sm text-gray-400">{{ __('market.hero_text') }}</p></div>
    <div><p class="text-white font-semibold mb-3">{{ __('market.f_sell') }}</p><ul class="space-y-2 text-sm"><li><a class="hover:text-white" href="{{ route('register') }}">{{ __('market.open_store') }}</a></li><li><a class="hover:text-white" href="{{ route('login') }}">{{ __('market.login') }}</a></li></ul></div>
    <div><p class="text-white font-semibold mb-3">{{ __('market.f_help') }}</p><ul class="space-y-2 text-sm"><li><a class="hover:text-white" href="/#faq">{{ __('market.faq_t') }}</a></li><li><a class="hover:text-white" href="/#how">{{ __('market.how_t') }}</a></li><li><a class="hover:text-white" href="{{ route('domains.show') }}">{{ __('market.domains_title') }}</a></li></ul></div>
    <div><p class="text-white font-semibold mb-3">{{ __('market.pay_strip') }}</p><div class="flex flex-wrap gap-2 text-xs font-semibold">
        <span class="px-2 py-1 rounded bg-[#e2136e] text-white">bKash</span><span class="px-2 py-1 rounded bg-[#f6921e] text-white">Nagad</span><span class="px-2 py-1 rounded bg-[#8c3494] text-white">Rocket</span><span class="px-2 py-1 rounded bg-emerald-600 text-white">{{ __('market.cod') }}</span></div></div>
</div><div class="border-t border-white/10 text-center text-xs text-gray-500 py-4">&copy; {{ date('Y') }} {{ __('market.brand') }}</div></footer>
</body></html>
