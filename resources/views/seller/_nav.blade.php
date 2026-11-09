<div class="max-w-4xl mx-auto px-4 sm:px-6 pt-6 flex flex-wrap gap-4 text-sm">
    @php
        $newOrders = \App\Models\Order::whereNull('seller_seen_at')->count();
        $pendingReviews = \App\Models\ProductReview::where('status', 'pending')->count();
        $pendingQuestions = \App\Models\ProductQuestion::whereNull('answer')->where('is_hidden', false)->count();
    @endphp
    @foreach ([['seller.dashboard','Overview','seller.dashboard'],['seller.orders.index','Orders','seller.orders.*'],['seller.products.index','Products','seller.products.*'],['seller.categories.index','Categories','seller.categories.*'],['seller.landing.index','Landing Pages','seller.landing.*'],['seller.reviews.index','Reviews','seller.reviews.*'],['seller.questions.index','Questions','seller.questions.*'],['seller.appearance.edit','Appearance','seller.appearance.*'],['seller.settings.edit','Settings','seller.settings.*']] as [$r,$l,$pat])
        <a class="{{ request()->routeIs($pat) ? 'font-semibold text-indigo-700' : 'text-gray-600' }}" href="{{ route($r) }}">{{ $l }}
            @if ($r === 'seller.orders.index' && $newOrders > 0) <span class="ml-1 bg-red-600 text-white rounded-full px-2 text-xs">{{ $newOrders }} new</span>@endif
            @if ($r === 'seller.reviews.index' && $pendingReviews > 0) <span class="ml-1 bg-red-600 text-white rounded-full px-2 text-xs">{{ $pendingReviews }}</span>@endif
            @if ($r === 'seller.questions.index' && $pendingQuestions > 0) <span class="ml-1 bg-red-600 text-white rounded-full px-2 text-xs">{{ $pendingQuestions }}</span>@endif
        </a>
    @endforeach
</div>
