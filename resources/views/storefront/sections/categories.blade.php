@if ($categories->count() > 0)
<section class="mb-6 flex flex-wrap gap-2">
    <a href="/" class="sf-chip {{ ! $activeCategory ? 'on' : '' }}">All</a>
    @foreach ($categories as $c)<a href="/?c={{ $c->slug }}" class="sf-chip {{ $activeCategory?->id === $c->id ? 'on' : '' }}">{{ $c->name }}</a>@endforeach
</section>
@endif
