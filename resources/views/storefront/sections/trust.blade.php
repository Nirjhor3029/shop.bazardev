@if ($methods->count() > 0)
<section class="sf-trust mb-8">
    <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 py-3">
        @foreach ($methods as $m)<span class="sf-trust-chip">{{ __('store.'.$m) }}</span>@endforeach
    </div>
    <p class="text-center text-xs text-gray-500">{{ __('store.trust_note') }}</p>
</section>
@endif
