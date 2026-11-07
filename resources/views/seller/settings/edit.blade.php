<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Store settings</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('seller.settings.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">@csrf @method('PUT')
            <div><label class="block text-sm font-medium">Store name</label><input name="name" value="{{ old('name', $store->name) }}" required class="mt-1 border-gray-300 rounded-md w-full"></div>
            <div><label class="block text-sm font-medium">Store color</label><input type="color" name="color" value="{{ old('color', $store->settings['color'] ?? '#4f46e5') }}" class="mt-1 h-10 w-20"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Delivery charge</label><input type="number" step="1" min="0" name="delivery_charge" value="{{ old('delivery_charge', $store->settings['delivery_charge'] ?? 0) }}" required class="mt-1 border-gray-300 rounded-md w-full"></div>
                <div><label class="block text-sm font-medium">Free delivery over (0 = never)</label><input type="number" step="1" min="0" name="free_delivery_over" value="{{ old('free_delivery_over', $store->settings['free_delivery_over'] ?? 0) }}" class="mt-1 border-gray-300 rounded-md w-full"></div>
            </div>
            <div class="border-t pt-4"><h3 class="font-semibold">Payment methods</h3>
                <label class="mt-3 flex items-center gap-2"><input type="checkbox" name="cod" value="1" class="rounded" @checked(old('cod', $methods['cod']->is_active ?? true))> Cash on delivery</label>
                @foreach ($manual as $type => $label)
                    @php $m = $methods[$type] ?? null; @endphp
                    <div class="mt-4 p-3 border rounded space-y-2">
                        <label class="flex items-center gap-2"><input type="checkbox" name="pay_on[{{ $type }}]" value="1" class="rounded" @checked(old("pay_on.$type", $m?->is_active))> {{ $label }} (customer sends money to your number)</label>
                        <input name="pay[{{ $type }}][number]" value="{{ old("pay.$type.number", $m?->number) }}" placeholder="Your {{ $label }} number, 01XXXXXXXXX" class="border-gray-300 rounded-md w-full">
                        @error("pay.$type.number")<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <input name="pay[{{ $type }}][instructions]" value="{{ old("pay.$type.instructions", $m?->instructions) }}" placeholder="Note for customer (optional), e.g. Personal / Merchant" class="border-gray-300 rounded-md w-full">
                    </div>
                @endforeach
                <p class="mt-2 text-xs text-gray-500">A method is shown only when it is ticked and has a number.</p>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
