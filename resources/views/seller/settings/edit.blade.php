<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Store settings</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('seller.settings.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">@csrf @method('PUT')
            <div><label class="block text-sm font-medium">Store name</label><input name="name" value="{{ old('name', $store->name) }}" required class="mt-1 border-gray-300 rounded-md w-full"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Delivery charge</label><input type="number" step="1" min="0" name="delivery_charge" value="{{ old('delivery_charge', $store->settings['delivery_charge'] ?? 0) }}" required class="mt-1 border-gray-300 rounded-md w-full"></div>
                <div><label class="block text-sm font-medium">Free delivery over (0 = never)</label><input type="number" step="1" min="0" name="free_delivery_over" value="{{ old('free_delivery_over', $store->settings['free_delivery_over'] ?? 0) }}" class="mt-1 border-gray-300 rounded-md w-full"></div>
                <div class="col-span-2"><label class="block text-sm font-medium">Delivery time estimate (optional, shown on product page)</label><input name="delivery_estimate" maxlength="100" placeholder="e.g. ২-৪ দিনের মধ্যে ডেলিভারি" value="{{ old('delivery_estimate', $store->settings['delivery_estimate'] ?? '') }}" class="mt-1 border-gray-300 rounded-md w-full">
                    @error('delivery_estimate')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
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
            @php $plan = $store->activePlan(); @endphp
            <div class="border-t pt-4"><h3 class="font-semibold">SSLCommerz (real-time bKash/Nagad/Rocket/card payment)</h3>
                @php $ssl = $methods['sslcommerz'] ?? null; @endphp
                @if (! $plan->allow_sslcommerz)
                    <p class="mt-1 text-sm text-amber-600">Not available on your current plan ({{ $plan->name }}). Upgrade to enable real-time online payment.</p>
                @else
                <p class="mt-1 text-xs text-gray-500">Use your own SSLCommerz merchant account - register at sslcommerz.com to get a Store ID and Store Password. We never see or hold the money; SSLCommerz pays it to your account directly.</p>
                <div class="mt-3 p-3 border rounded space-y-2">
                    <label class="flex items-center gap-2"><input type="checkbox" name="sslcommerz_on" value="1" class="rounded" @checked(old('sslcommerz_on', $ssl?->is_active))> Enable SSLCommerz</label>
                    <input name="sslcommerz_store_id" value="{{ old('sslcommerz_store_id', $ssl?->config['store_id'] ?? '') }}" placeholder="Store ID" class="border-gray-300 rounded-md w-full">
                    <input name="sslcommerz_store_passwd" type="password" placeholder="{{ $ssl?->secret ? 'Saved - leave blank to keep it' : 'Store Password' }}" class="border-gray-300 rounded-md w-full">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="sslcommerz_sandbox" value="1" class="rounded" @checked(old('sslcommerz_sandbox', $ssl?->config['sandbox'] ?? true))> Sandbox / test mode (uncheck once you have a live account)</label>
                </div>
                @endif
            </div>
            <div class="border-t pt-4"><h3 class="font-semibold">Domain</h3>
                <p class="text-sm text-gray-600">Your store's address: <a href="{{ $store->url() }}" target="_blank" rel="noopener" class="text-indigo-600 underline">{{ $store->url() }}</a>
                    @unless ($plan->allow_subdomain)<span class="text-amber-600"> - upgrade for a branded subdomain ({{ $store->slug }}.{{ config('shop.base_domain') }})</span>@endunless</p>
                <label class="block text-sm font-medium mt-3 text-gray-400">Custom domain (coming soon)</label>
                <input type="text" disabled placeholder="www.yourshop.com" class="mt-1 border-gray-200 bg-gray-50 rounded-md w-full text-gray-400">
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
