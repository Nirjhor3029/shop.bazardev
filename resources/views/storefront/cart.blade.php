@extends('storefront.layout')
@section('title', 'Cart - '.$store->name)
@section('content')
<h1 class="text-2xl font-bold mb-4">Your cart</h1>
@if ($lines->isEmpty())
    <p class="text-gray-500">Your cart is empty. <a class="underline" href="/">Continue shopping</a></p>
@else
    <form method="POST" action="/cart/update" class="bg-white rounded-lg shadow-sm divide-y">@csrf
        @foreach ($lines as $l)
            <div class="p-4 flex items-center gap-4">
                <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden shrink-0">@if ($l['product']->coverUrl())<img src="{{ $l['product']->coverUrl() }}" class="w-full h-full object-cover" alt="">@endif</div>
                <div class="flex-1 min-w-0"><p class="font-medium truncate">{{ $l['product']->name }}</p><p class="text-sm text-gray-500">{{ $cur }} {{ number_format($l['price']) }}</p></div>
                <input type="number" name="qty[{{ $l['product']->id }}]" value="{{ $l['qty'] }}" min="0" max="{{ min(99, $l['product']->stock) }}" class="w-20 border-gray-300 rounded-md">
                <p class="w-24 text-right font-semibold">{{ number_format($l['total']) }}</p>
            </div>
        @endforeach
        <div class="p-4 flex items-center justify-between"><button class="text-sm underline">Update cart</button><p class="font-semibold">Subtotal: {{ $cur }} {{ number_format($subtotal) }}</p></div>
    </form>
    <a href="/checkout" class="mt-5 inline-block text-white px-6 py-3 rounded-md" style="background: {{ $color }}">Checkout</a>
@endif
@endsection
