<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Custom Domain</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">

        @if (session('status'))<div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('status') }}</div>@endif

        @unless ($plan->allow_custom_domain)
            <div class="rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                Custom domain is a Pro plan feature. Upgrade your plan to point your own domain (e.g. <b>yourshop.com</b>) at your store.
            </div>
        @endunless

        @if ($domain)
            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="font-medium text-lg">{{ $domain->domain }}</p>
                    <span class="text-xs rounded-full px-2 py-1
                        @if ($domain->status === 'verified') bg-green-100 text-green-700
                        @elseif ($domain->status === 'failed') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700 @endif">
                        {{ ucfirst($domain->status) }}
                    </span>
                </div>

                @if ($domain->status !== 'verified')
                    <div class="text-sm text-gray-600 space-y-2 border-t pt-3">
                        <p>Add these two DNS records at your domain's provider:</p>
                        <table class="w-full text-xs border rounded overflow-hidden">
                            <tr class="bg-gray-50"><td class="p-2 font-medium">Type</td><td class="p-2 font-medium">Name</td><td class="p-2 font-medium">Value</td></tr>
                            <tr class="border-t"><td class="p-2">TXT</td><td class="p-2 break-all">{{ $domain->txtRecordName() }}</td><td class="p-2 break-all">{{ $domain->verification_token }}</td></tr>
                            <tr class="border-t"><td class="p-2">A</td><td class="p-2">{{ $domain->domain }}</td><td class="p-2">{{ config('shop.server_ip', 'your server\'s IP - ask support') }}</td></tr>
                        </table>
                        <p class="text-xs text-gray-500">DNS changes can take anywhere from a few minutes to a few hours to propagate. Press Verify once you've added the records.</p>
                        @if ($domain->last_checked_at)<p class="text-xs text-gray-400">Last checked {{ $domain->last_checked_at->diffForHumans() }}.</p>@endif
                    </div>
                @else
                    <p class="text-sm text-green-700 border-t pt-3">Verified {{ $domain->verified_at->diffForHumans() }}. Routing your store's traffic through this domain is a separate step still being built - this domain won't serve your storefront yet.</p>
                @endif

                <div class="flex gap-3 pt-2">
                    @if ($domain->status !== 'verified')
                        <form method="POST" action="{{ route('seller.custom-domain.recheck', $domain) }}">@csrf<x-primary-button>Verify</x-primary-button></form>
                    @endif
                    <form method="POST" action="{{ route('seller.custom-domain.destroy', $domain) }}" onsubmit="return confirm('Remove this domain?')">@csrf @method('DELETE')<x-secondary-button>Remove</x-secondary-button></form>
                </div>
            </div>
        @elseif ($plan->allow_custom_domain)
            <form method="POST" action="{{ route('seller.custom-domain.store') }}" class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">
                @csrf
                <x-input-label for="domain" value="Your domain" />
                <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" placeholder="yourshop.com" value="{{ old('domain') }}" required />
                <x-input-error :messages="$errors->get('domain')" class="mt-1" />
                <x-primary-button>Add domain</x-primary-button>
            </form>
        @endif
    </div></div>
</x-app-layout>
