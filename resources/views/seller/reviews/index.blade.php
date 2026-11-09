<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Reviews</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<p class="text-sm text-green-700">{{ session('status') }}</p>@endif
        <div class="flex flex-wrap gap-3 text-sm">
            @foreach (['pending', 'approved', 'rejected'] as $s)
                <a href="{{ route('seller.reviews.index', ['status' => $s]) }}" class="{{ $status === $s ? 'font-semibold' : 'text-gray-600' }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($reviews as $r)
                <div class="p-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $r->product?->name ?? 'Deleted product' }} · {{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</p>
                        <p class="text-sm text-gray-600">{{ $r->customer_name }} · {{ $r->created_at->diffForHumans() }}</p>
                        @if ($r->comment)<p class="text-sm text-gray-700 mt-1">{{ $r->comment }}</p>@endif
                    </div>
                    @if ($status === 'pending')
                        <div class="flex gap-2 shrink-0">
                            <form method="POST" action="{{ route('seller.reviews.update', $r) }}">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="text-sm text-green-700 underline">Approve</button></form>
                            <form method="POST" action="{{ route('seller.reviews.update', $r) }}">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="text-sm text-red-600 underline">Reject</button></form>
                        </div>
                    @endif
                </div>
            @empty<p class="p-6 text-gray-500">No {{ $status }} reviews.</p>@endforelse
        </div>
        {{ $reviews->links() }}
    </div></div>
</x-app-layout>
