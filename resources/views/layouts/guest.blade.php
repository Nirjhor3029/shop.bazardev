<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('market.brand') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
<div class="min-h-screen grid lg:grid-cols-2">
    <div class="flex flex-col px-6 py-8 sm:px-12 bg-white">
        <div class="flex items-center justify-between">
            <a href="/" class="flex items-center gap-2"><x-application-logo class="h-9 w-9" /><span class="font-bold text-lg">{{ __('market.brand') }}</span></a>
            <a href="{{ route('lang', app()->getLocale() === 'bn' ? 'en' : 'bn') }}" class="text-xs border rounded px-2 py-1 text-gray-600 hover:bg-gray-50">{{ app()->getLocale() === 'bn' ? 'English' : 'বাংলা' }}</a>
        </div>
        <div class="flex-1 flex items-center"><div class="w-full max-w-md mx-auto py-10">{{ $slot }}</div></div>
        <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} {{ __('market.brand') }}</p>
    </div>
    <aside class="hidden lg:flex flex-col justify-center bg-gradient-to-br from-brand to-ink text-white px-14">
        <h2 class="text-3xl font-bold leading-snug">{{ __('market.auth_title') }}</h2>
        <ul class="mt-8 space-y-4 text-lg">
            @foreach (['auth_1','auth_2','auth_3'] as $k)<li class="flex gap-3"><span class="mt-1 w-6 h-6 shrink-0 rounded-full bg-white/20 flex items-center justify-center text-sm">✓</span>{{ __("market.$k") }}</li>@endforeach
        </ul>
        <div class="mt-10 flex flex-wrap gap-2 text-xs font-semibold"><span class="px-2.5 py-1 rounded bg-white/15">bKash</span><span class="px-2.5 py-1 rounded bg-white/15">Nagad</span><span class="px-2.5 py-1 rounded bg-white/15">Rocket</span><span class="px-2.5 py-1 rounded bg-white/15">{{ __('market.cod') }}</span></div>
    </aside>
</div>
</body></html>
