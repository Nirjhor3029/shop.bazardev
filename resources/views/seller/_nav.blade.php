<div class="max-w-4xl mx-auto px-4 sm:px-6 pt-6 flex flex-wrap gap-4 text-sm">
    @foreach ([['seller.dashboard','Overview','seller.dashboard'],['seller.orders.index','Orders','seller.orders.*'],['seller.products.index','Products','seller.products.*'],['seller.categories.index','Categories','seller.categories.*'],['seller.settings.edit','Settings','seller.settings.*']] as [$r,$l,$pat])
        <a class="{{ request()->routeIs($pat) ? 'font-semibold text-indigo-700' : 'text-gray-600' }}" href="{{ route($r) }}">{{ $l }}</a>
    @endforeach
</div>
