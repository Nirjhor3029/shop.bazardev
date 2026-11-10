<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Domain leads</h2></x-slot>
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        <p class="text-sm text-gray-600">Every domain a visitor (or seller) found available through the public checker, newest first.</p>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($domains as $d)
                <div class="p-4 flex items-center justify-between gap-3">
                    <div><p class="font-medium">{{ $d->domain }}</p>
                        <p class="text-xs text-gray-500">{{ $d->user?->name ?? 'Anonymous visitor' }} · {{ $d->ip }} · {{ $d->created_at->diffForHumans() }}</p></div>
                </div>
            @empty<p class="p-6 text-gray-500">No domain checks yet.</p>@endforelse
        </div>
        {{ $domains->links() }}
    </div></div>
</x-app-layout>
