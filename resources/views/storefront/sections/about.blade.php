@if ($look['about'] || $look['phone'] || $look['email'] || $look['address'] || $look['facebook'])
<section class="sf-card p-6 mb-8">
    <h2 class="text-xl font-semibold mb-2">About us</h2>
    @if ($look['about'])<p class="whitespace-pre-line">{{ $look['about'] }}</p>@endif
    <div class="mt-4 text-sm space-y-1 text-gray-500">
        @if ($look['phone'])<p>Phone: {{ $look['phone'] }}</p>@endif
        @if ($look['email'])<p>Email: {{ $look['email'] }}</p>@endif
        @if ($look['address'])<p>Address: {{ $look['address'] }}</p>@endif
        @if ($look['facebook'])<p><a class="underline" href="{{ $look['facebook'] }}" rel="noopener nofollow" target="_blank">Facebook page</a></p>@endif
    </div>
</section>
@endif
