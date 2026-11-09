@extends('storefront.layout')
@section('title', __('store.wishlist').' - '.$store->name)
@section('content')
<h1 class="text-xl font-semibold mb-4">{{ __('store.wishlist') }}</h1>
<div id="sf-wishlist-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4"></div>
<p id="sf-wishlist-empty" class="text-gray-500 hidden">{{ __('store.wishlist_empty') }}</p>
<script>
(function () {
    try {
        if (window.sfWishlist) {
            window.sfWishlist.render('sf-wishlist-grid', 'sf-wishlist-empty');
        }
    } catch (e) {}
})();
</script>
@endsection
