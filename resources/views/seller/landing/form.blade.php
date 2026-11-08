@php $editing = $lp->exists; @endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit landing page' : 'New landing page' }}</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ $editing ? route('seller.landing.update', $lp) : route('seller.landing.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf @if ($editing) @method('PUT') @endif
            <div><label class="block text-sm font-medium text-gray-700">Product</label>
                <select name="product_id" required class="mt-1 border-gray-300 rounded-md w-full">
                    <option value="">Choose a product</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}" @selected(old('product_id', $lp->product_id) == $p->id)>{{ $p->name }}</option>@endforeach
                </select>@error('product_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @if ($products->isEmpty())<p class="text-sm text-amber-600 mt-1">Add a product first.</p>@endif</div>
            <div><label class="block text-sm font-medium text-gray-700">Headline</label>
                <input name="headline" maxlength="150" value="{{ old('headline', $lp->headline) }}" placeholder="e.g. Original Leather Wallet - today only 500 taka" class="mt-1 border-gray-300 rounded-md w-full">@error('headline')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-gray-700">Subheadline (optional)</label>
                <input name="subheadline" maxlength="300" value="{{ old('subheadline', $lp->subheadline) }}" placeholder="One line under the headline" class="mt-1 border-gray-300 rounded-md w-full">@error('subheadline')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-gray-700">Benefits (one per line, max 6)</label>
                <textarea name="benefits" rows="5" placeholder="Free delivery&#10;7-day return&#10;Original product guarantee" class="mt-1 border-gray-300 rounded-md w-full">{{ old('benefits', $lp->benefits ? implode("\n", $lp->benefits) : '') }}</textarea></div>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $lp->is_published)) class="rounded"> Published (page is live)</label>
            @if ($editing && $lp->is_published)<p class="text-sm text-gray-500">Link: <a href="{{ $lp->url() }}" target="_blank" rel="noopener" class="text-indigo-600 underline break-all">{{ $lp->url() }}</a></p>@endif
            <div class="flex items-center gap-3"><x-primary-button>{{ $editing ? 'Save' : 'Create' }}</x-primary-button>
                <a href="{{ route('seller.landing.index') }}" class="text-sm text-gray-600">Back</a></div>
        </form>
        @if ($editing)
            <form method="POST" action="{{ route('seller.landing.destroy', $lp) }}" x-data="{ sure: false }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600" x-text="sure ? 'Click again to really delete' : 'Delete landing page'"></button>
            </form>
        @endif
    </div></div>
</x-app-layout>
