<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Products</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <a href="{{ route('seller.products.create') }}" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">+ Add product</a>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($products as $p)
                <a href="{{ route('seller.products.edit', $p) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50">
                    <div class="w-14 h-14 bg-gray-100 rounded overflow-hidden shrink-0">@if ($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" class="w-full h-full object-cover">@endif</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium truncate">{{ $p->name }} @if ($p->is_sample)<span class="text-xs text-amber-600">(sample)</span>@endif</p>
                        <p class="text-sm text-gray-500">{{ $p->category?->name ?? 'No category' }} · stock {{ $p->stock }} · {{ $p->is_active ? 'visible' : 'hidden' }}</p>
                    </div>
                    <p class="font-semibold shrink-0">{{ number_format($p->currentPrice()) }}</p>
                </a>
            @empty<p class="p-6 text-gray-500">No products yet.</p>@endforelse
        </div>
        {{ $products->links() }}
    </div></div>
</x-app-layout>
