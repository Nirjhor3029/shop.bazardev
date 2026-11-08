<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Landing Pages</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <p class="text-sm text-gray-500">একটা পণ্যের জন্য আলাদা অর্ডার-ফর্ম পেজ — Facebook-এ boost করার জন্য বানানো, cart ছাড়াই সরাসরি অর্ডার নেয়।</p>
        <a href="{{ route('seller.landing.create') }}" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">+ Add landing page</a>
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($pages as $lp)
                <div class="flex items-center gap-4 p-4">
                    <div class="w-14 h-14 bg-gray-100 rounded overflow-hidden shrink-0">@if ($lp->product?->coverUrl())<img src="{{ $lp->product->coverUrl() }}" alt="" class="w-full h-full object-cover">@endif</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium truncate">{{ $lp->headline ?: $lp->product?->name }}</p>
                        <p class="text-sm text-gray-500 truncate">{{ $lp->product?->name }} · {{ $lp->is_published ? 'published' : 'draft' }}</p>
                        @if ($lp->is_published)<a href="{{ $lp->url() }}" target="_blank" rel="noopener" class="text-xs text-indigo-600 underline break-all">{{ $lp->url() }}</a>@endif
                    </div>
                    <a href="{{ route('seller.landing.edit', $lp) }}" class="text-sm text-indigo-600 shrink-0">Edit</a>
                </div>
            @empty<p class="p-6 text-gray-500">No landing pages yet.</p>@endforelse
        </div>
    </div></div>
</x-app-layout>
