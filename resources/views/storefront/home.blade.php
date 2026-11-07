@extends('storefront.layout')
@section('content')
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @forelse ($products as $p)
            <a href="/p/{{ $p->slug }}" class="block bg-white rounded-lg shadow-sm p-4">
                <div class="aspect-square bg-gray-100 rounded mb-3 overflow-hidden">@if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover">@endif</div>
                <h2 class="font-medium">{{ $p->name }}</h2>
                <p class="mt-1">@if ($p->sale_price)<span class="font-semibold">{{ $cur }} {{ number_format($p->sale_price) }}</span> <s class="text-gray-400 text-sm">{{ number_format($p->price) }}</s>@else<span class="font-semibold">{{ $cur }} {{ number_format($p->price) }}</span>@endif</p>
                @if ($p->stock < 1)<p class="text-xs text-red-600">Out of stock</p>@endif
            </a>
        @empty<p class="col-span-full text-gray-500">No products yet.</p>@endforelse
    </div>
@endsection
