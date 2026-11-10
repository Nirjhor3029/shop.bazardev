<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Domain checker</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        <p class="text-sm text-gray-600">Check if a domain name is available to register. Every domain you find available here is saved to the list below automatically.</p>

        <form method="POST" action="{{ route('domains.check') }}" class="flex gap-2">
            @csrf
            <input type="text" name="domain" value="{{ old('domain') }}" required maxlength="255" placeholder="yourbrand.com" class="flex-1 rounded-md border-gray-300">
            <x-primary-button>Check</x-primary-button>
        </form>
        @error('domain')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        @if (session('domainResult'))
            @php $r = session('domainResult'); @endphp
            <div class="rounded-md border p-3 text-sm
                @if ($r['status'] === 'available') bg-green-50 border-green-200
                @elseif ($r['status'] === 'taken') bg-red-50 border-red-200
                @else bg-gray-50 border-gray-200 @endif">
                <b>{{ $r['domain'] }}</b> —
                @if ($r['status'] === 'available') <span class="text-green-700">available</span>
                @elseif ($r['status'] === 'taken') <span class="text-red-700">already taken</span>
                @elseif ($r['status'] === 'invalid') <span class="text-gray-600">that doesn't look like a valid domain</span>
                @else <span class="text-gray-600">couldn't check this one right now</span>
                @endif
            </div>
        @endif

        @if (session('domainAlternatives'))
            <div class="rounded-lg border divide-y overflow-hidden">
                @foreach (session('domainAlternatives') as $alt)
                    <div class="px-3 py-2 text-xs flex items-center justify-between gap-2
                        @if ($alt['status'] === 'available') bg-green-50
                        @elseif ($alt['status'] === 'taken') bg-red-50
                        @else bg-gray-50 @endif">
                        <span>{{ $alt['domain'] }}</span>
                        @if ($alt['status'] === 'available')<span class="text-green-700 font-semibold shrink-0">available</span>
                        @elseif ($alt['status'] === 'taken')<span class="text-red-700 shrink-0">taken</span>
                        @else <span class="text-gray-500 shrink-0">?</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-lg divide-y mt-4">
            @forelse ($domains as $d)
                <div class="p-3 flex items-center justify-between gap-3">
                    <div><p class="font-medium">{{ $d->domain }}</p><p class="text-xs text-gray-500">Available at check time · {{ $d->created_at->diffForHumans() }}</p></div>
                    <form method="POST" action="{{ route('seller.domains.destroy', $d) }}">@csrf @method('DELETE')<button class="text-xs text-red-600 underline">Remove</button></form>
                </div>
            @empty<p class="p-6 text-gray-500">No saved domains yet - check one above.</p>@endforelse
        </div>
        {{ $domains->links() }}
    </div></div>
</x-app-layout>
