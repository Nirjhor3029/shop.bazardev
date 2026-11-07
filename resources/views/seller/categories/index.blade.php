<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Categories</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('seller.categories.store') }}" class="flex gap-2">@csrf
            <input name="name" required maxlength="80" placeholder="New category" class="border-gray-300 rounded-md flex-1"><x-primary-button>Add</x-primary-button></form>
        @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @foreach ($categories as $c)
                <div class="p-4 flex items-center justify-between"><span>{{ $c->name }} <span class="text-xs text-gray-500">({{ $c->products_count }})</span></span>
                    <form method="POST" action="{{ route('seller.categories.destroy', $c) }}">@csrf @method('DELETE')<button class="text-sm text-red-600">Delete</button></form></div>
            @endforeach
        </div>
    </div></div>
</x-app-layout>
