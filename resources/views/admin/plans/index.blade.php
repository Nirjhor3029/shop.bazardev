<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Super admin: plans</h2></x-slot>
    <div class="py-8"><div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.index') }}" class="text-sm text-indigo-600">&larr; Stores</a>
            <a href="{{ route('admin.plans.create') }}" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">+ Add plan</a>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="p-3">Plan</th><th class="p-3">Price/mo</th><th class="p-3">Products</th><th class="p-3">Landing pages</th><th class="p-3">Subdomain</th><th class="p-3">SSLCommerz</th><th class="p-3">Pixel</th><th class="p-3">Stores</th><th class="p-3"></th></tr></thead>
                <tbody class="divide-y">
                @foreach ($plans as $p)
                    <tr class="{{ ! $p->is_active ? 'opacity-50' : '' }}">
                        <td class="p-3 font-medium">{{ $p->name }} @if ($p->is_default)<span class="text-xs text-indigo-600">(default)</span>@endif</td>
                        <td class="p-3">৳{{ number_format($p->price, 2) }}</td>
                        <td class="p-3">{{ $p->max_products ?? 'Unlimited' }}</td>
                        <td class="p-3">{{ $p->max_landing_pages ?? 'Unlimited' }}</td>
                        <td class="p-3">{{ $p->allow_subdomain ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ $p->allow_sslcommerz ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ $p->allow_pixel_tracking ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ $p->stores()->count() }}</td>
                        <td class="p-3"><a href="{{ route('admin.plans.edit', $p) }}" class="text-indigo-600">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</x-app-layout>
