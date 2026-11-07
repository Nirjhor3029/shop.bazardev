<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $store->name }}</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <p class="text-sm text-gray-500">Your store ({{ $store->status }})</p>
            <p class="mt-1 break-all font-medium text-indigo-600">{{ $store->url() }}</p>
            <form method="POST" action="{{ route('seller.publish') }}" class="mt-4">@csrf
                <input type="hidden" name="publish" value="{{ $store->status === 'published' ? 0 : 1 }}">
                <x-primary-button>{{ $store->status === 'published' ? 'Hide store' : 'Publish store' }}</x-primary-button>
            </form>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold">Setup checklist</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li>✅ Store created, Cash on Delivery on</li>
                <li>{{ $hasRealProduct ? '✅' : '⬜' }} <a class="text-indigo-600" href="{{ route('seller.products.index') }}">Add your first product</a> ({{ $products }} products now)</li>
                <li><a class="text-indigo-600" href="{{ route('seller.settings.edit') }}">bKash / Nagad number</a></li>
                <li><a class="text-indigo-600" href="{{ route('seller.settings.edit') }}">Delivery charge</a></li>
                <li>{{ $store->status === 'published' ? '✅' : '⬜' }} Publish</li>
            </ul>
            <p class="mt-3 text-xs text-gray-500">Logo, banner and themes come in the next step.</p>
        </div>
    </div></div>
</x-app-layout>
