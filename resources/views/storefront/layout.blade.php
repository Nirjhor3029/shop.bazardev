@php $cartCount = (new \App\Support\Cart($store->id))->count(); @endphp
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $store->name)</title>
@yield('head')
@vite(['resources/css/app.css'])</head>
<body class="bg-gray-50 text-gray-900 antialiased">
<header class="text-white" style="background: {{ $color }}"><div class="max-w-5xl mx-auto px-5 py-5 flex items-center justify-between">
    <a href="/" class="text-xl font-bold">{{ $store->name }}</a>
    <a href="/cart" class="text-sm bg-white/20 rounded-full px-4 py-1">Cart ({{ $cartCount }})</a></div></header>
<main class="max-w-5xl mx-auto px-5 py-8">
    @if (session('status'))<div class="mb-4 bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
    @yield('content')
</main></body></html>
