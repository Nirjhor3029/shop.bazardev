@php $editing = $product->exists; @endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit product' : 'Add product' }}</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        @error('limit')<div class="bg-amber-50 text-amber-700 p-3 rounded">{{ $message }}</div>@enderror
        <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('seller.products.update', $product) : route('seller.products.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf @if ($editing) @method('PUT') @endif
            <div><label class="block text-sm font-medium text-gray-700">Name</label>
                <input name="name" value="{{ old('name', $product->name) }}" required maxlength="150" class="mt-1 border-gray-300 rounded-md w-full">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="4" class="mt-1 border-gray-300 rounded-md w-full">{{ old('description', $product->description) }}</textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required class="mt-1 border-gray-300 rounded-md w-full">@error('price')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700">Sale price (optional)</label>
                    <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="mt-1 border-gray-300 rounded-md w-full">@error('sale_price')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700">Stock</label>
                    <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" required class="mt-1 border-gray-300 rounded-md w-full"></div>
                <div><label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" class="mt-1 border-gray-300 rounded-md w-full"><option value="">None</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            </div>
            @if ($editing && $product->images->count())
                <div><p class="text-sm font-medium text-gray-700">Images (tick to remove)</p>
                    <div class="mt-2 flex flex-wrap gap-3">@foreach ($product->images as $img)
                        <label class="block text-center text-xs"><img src="{{ $img->url() }}" class="w-20 h-20 object-cover rounded border"><input type="checkbox" name="remove_images[]" value="{{ $img->id }}"> remove</label>
                    @endforeach</div></div>
            @endif
            <div><label class="block text-sm font-medium text-gray-700">Add images (jpg, png, webp, up to 2 MB each)</label>
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 text-sm">
                @error('images')<p class="text-sm text-red-600">{{ $message }}</p>@enderror @error('images.*')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded"> Visible in store</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded"> Featured (show in the homepage spotlight strip)</label>
            <div class="flex items-center gap-3"><x-primary-button>{{ $editing ? 'Save' : 'Add product' }}</x-primary-button>
                <a href="{{ route('seller.products.index') }}" class="text-sm text-gray-600">Back</a></div>
        </form>
        @if ($editing)
            <form method="POST" action="{{ route('seller.products.destroy', $product) }}" x-data="{ sure: false }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600" x-text="sure ? 'Click again to really delete' : 'Delete product'"></button>
            </form>
        @endif
    </div></div>
</x-app-layout>
