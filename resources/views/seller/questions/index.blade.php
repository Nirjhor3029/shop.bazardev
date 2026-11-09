<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Questions</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<p class="text-sm text-green-700">{{ session('status') }}</p>@endif
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('seller.questions.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'font-semibold' : 'text-gray-600' }}">Pending</a>
            <a href="{{ route('seller.questions.index', ['status' => 'answered']) }}" class="{{ $status === 'answered' ? 'font-semibold' : 'text-gray-600' }}">Answered</a>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($questions as $q)
                <div class="p-4">
                    <p class="font-medium">{{ $q->product?->name ?? 'Deleted product' }}</p>
                    <p class="text-sm text-gray-700 mt-1">Q: {{ $q->question }} <span class="text-gray-400">— {{ $q->customer_name }}, {{ $q->created_at->diffForHumans() }}</span></p>
                    @if ($q->answer)
                        <p class="text-sm text-gray-700 mt-1">A: {{ $q->answer }}</p>
                    @else
                        <form method="POST" action="{{ route('seller.questions.update', $q) }}" class="mt-2 flex gap-2">
                            @csrf @method('PUT')
                            <input name="answer" placeholder="Write an answer..." class="border-gray-300 rounded-md text-sm flex-1" required maxlength="1000">
                            <button class="text-sm text-indigo-700 underline shrink-0">Answer</button>
                        </form>
                        <form method="POST" action="{{ route('seller.questions.update', $q) }}" class="mt-1">
                            @csrf @method('PUT')<input type="hidden" name="hide" value="1">
                            <button class="text-xs text-red-600 underline">Hide (spam)</button>
                        </form>
                    @endif
                </div>
            @empty<p class="p-6 text-gray-500">No {{ $status }} questions.</p>@endforelse
        </div>
        {{ $questions->links() }}
    </div></div>
</x-app-layout>
