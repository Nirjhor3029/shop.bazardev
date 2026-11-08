@extends('storefront.layout')
@section('title', __('store.cart_title').' - '.$store->name)
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ __('store.cart_title') }}</h1>
@if ($lines->isEmpty())
    <p class="text-gray-500">{{ __('store.cart_empty') }} <a class="underline" href="{{ $store->storePath('/') }}">{{ __('store.continue') }}</a></p>
@else
    <form method="POST" action="{{ $store->storePath('/cart/update') }}" class="sf-card divide-y">@csrf
        @foreach ($lines as $l)
            <div class="p-4 flex items-center gap-3">
                <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden shrink-0">@if ($l['product']->coverUrl())<img src="{{ $l['product']->coverUrl() }}" class="w-full h-full object-cover" alt="">@endif</div>
                <div class="flex-1 min-w-0"><p class="font-medium truncate">{{ $l['product']->name }}</p><p class="text-sm text-gray-500">{{ \App\Support\Fmt::money($l['price']) }}</p></div>
                <input type="number" name="qty[{{ $l['product']->id }}]" value="{{ $l['qty'] }}" min="0" max="{{ min(99, $l['product']->stock) }}" class="w-16 border-gray-300 rounded-md text-center" aria-label="{{ __('store.qty') }}">
                <p class="w-20 text-right font-semibold">{{ \App\Support\Fmt::money($l['total']) }}</p>
            </div>
        @endforeach
        <div class="p-4 flex items-center justify-between"><button class="text-sm underline">{{ __('store.update_cart') }}</button><p class="font-semibold">{{ __('store.subtotal') }}: {{ \App\Support\Fmt::money($subtotal) }}</p></div>
    </form>
    <a href="{{ $store->storePath('/checkout') }}" class="sf-btn mt-5 w-full md:w-auto">{{ __('store.checkout') }}</a>
@endif
@endsection
