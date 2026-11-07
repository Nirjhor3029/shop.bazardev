@extends('storefront.layout')
@section('title', $product->name.' - '.$store->name)
@section('head')<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150) }}">@endsection
@section('content')
<div class="grid md:grid-cols-2 gap-8">
    <div class="space-y-3">@forelse ($product->images as $img)<img src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full rounded-lg bg-white">@empty<div class="aspect-square bg-gray-100 rounded-lg"></div>@endforelse</div>
    <div>
        <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
        <p class="mt-3 text-2xl font-semibold">{{ $cur }} {{ number_format($product->currentPrice()) }} @if ($product->sale_price)<s class="ml-2 text-base text-gray-400">{{ number_format($product->price) }}</s>@endif</p>
        <p class="mt-2 text-sm {{ $product->stock > 0 ? 'text-green-700' : 'text-red-600' }}">{{ $product->stock > 0 ? 'In stock' : 'Out of stock' }}</p>
        @if ($product->stock > 0)
            <form method="POST" action="/cart/add" class="mt-5 flex items-center gap-3">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="number" name="qty" value="1" min="1" max="{{ min(99, $product->stock) }}" class="w-20 border-gray-300 rounded-md">
                <button class="text-white px-5 py-2 rounded-md" style="background: {{ $color }}">Add to cart</button>
            </form>
        @endif
        @if ($product->description)<p class="mt-6 whitespace-pre-line">{{ $product->description }}</p>@endif
    </div>
</div>
@endsection
