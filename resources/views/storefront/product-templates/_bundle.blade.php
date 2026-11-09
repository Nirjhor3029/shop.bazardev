@if ($bundleItems->isNotEmpty())
<div class="mt-6 pt-6 border-t" id="sf-bundle" x-data="{ checked: {{ \Illuminate\Support\Js::from($bundleItems->pluck('id')->all()) }} }">
    <h2 class="font-semibold mb-3">{{ __('store.bundle_title') }}</h2>
    <div class="flex flex-wrap gap-3 mb-3">
        @foreach ($bundleItems as $item)
            <label class="flex items-center gap-2 text-sm border rounded-md p-2 {{ $item['id'] === $product->id ? 'bg-gray-50' : '' }}">
                <input type="checkbox" value="{{ $item['id'] }}" x-model="checked" @if ($item['id'] === $product->id) disabled @endif class="rounded">
                @if ($item['image'])<img src="{{ $item['image'] }}" alt="" class="w-10 h-10 object-cover rounded">@endif
                <span>{{ $item['name'] }}<br><span class="text-gray-500">{{ $item['priceText'] }}</span></span>
            </label>
        @endforeach
    </div>
    <form method="POST" action="{{ $store->storePath('/cart/add-bundle') }}">
        @csrf
        <template x-for="id in checked" :key="id"><input type="hidden" name="product_ids[]" :value="id"></template>
        <button class="bg-gray-900 text-white text-sm px-4 py-1.5 rounded-md">{{ __('store.add_bundle') }}</button>
    </form>
</div>
@endif
