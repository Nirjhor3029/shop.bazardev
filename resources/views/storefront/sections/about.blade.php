@if ($look['about'] || $look['phone'] || $look['email'] || $look['address'] || $look['facebook'])
<section class="sf-card p-6 mb-8">
    <h2 class="text-xl font-semibold mb-2">{{ __('store.about_us') }}</h2>
    @if ($look['about'])<p class="whitespace-pre-line">{{ $look['about'] }}</p>@endif
    <div class="mt-4 text-sm space-y-1 text-gray-500">
        @if ($look['phone'])<p>{{ __('store.phone') }}: {{ \App\Support\Fmt::digits($look['phone']) }}</p>@endif
        @if ($look['email'])<p>{{ __('store.email') }}: {{ $look['email'] }}</p>@endif
        @if ($look['address'])<p>{{ __('store.address') }}: {{ $look['address'] }}</p>@endif
        @if ($look['facebook'])<p><a class="underline" href="{{ $look['facebook'] }}" rel="noopener nofollow" target="_blank">{{ __('store.facebook') }}</a></p>@endif
    </div>
</section>
@endif
