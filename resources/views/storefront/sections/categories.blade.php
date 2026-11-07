@if ($categories->count() > 0)
<section class="mb-5 sf-chips-row">
    <a href="/" class="sf-chip {{ ! $activeCategory ? 'on' : '' }}">{{ __('store.all') }}</a>
    @foreach ($categories as $c)<a href="/?c={{ $c->slug }}" class="sf-chip {{ $activeCategory?->id === $c->id ? 'on' : '' }}">{{ $c->name }}</a>@endforeach
</section>
@endif
