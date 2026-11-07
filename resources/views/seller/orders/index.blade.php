<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Orders</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('seller.orders.index') }}" class="{{ ! $status ? 'font-semibold' : 'text-gray-600' }}">All</a>
            @foreach (array_keys(\App\Models\Order::FLOW) as $s)<a href="{{ route('seller.orders.index', ['status' => $s]) }}" class="{{ $status === $s ? 'font-semibold' : 'text-gray-600' }}">{{ ucfirst($s) }}</a>@endforeach
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($orders as $o)
                <a href="{{ route('seller.orders.show', $o) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50">
                    <div class="min-w-0"><p class="font-medium">{{ $o->number }} · {{ $o->customer_name }}</p>
                        <p class="text-sm text-gray-500">{{ $o->created_at->diffForHumans() }} · {{ strtoupper($o->payment_method) }} · pay: {{ $o->payment_status }}</p></div>
                    <div class="text-right shrink-0"><p class="font-semibold">{{ number_format($o->total) }}</p><p class="text-xs text-gray-500">{{ $o->status }}</p></div>
                </a>
            @empty<p class="p-6 text-gray-500">No orders yet.</p>@endforelse
        </div>
        {{ $orders->links() }}
    </div></div>
</x-app-layout>
