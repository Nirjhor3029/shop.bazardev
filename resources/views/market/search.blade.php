@extends('layouts.market')
@section('title', ($q ? $q.' · ' : '').__('market.brand'))
@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-xl font-bold mb-4">{{ __('market.results_for') }} “{{ $q }}”</h1>
    @if ($q !== '' && $results->count())
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">@foreach ($results as $p)@include('market._card')@endforeach</div>
        <div class="mt-6">{{ $results->links() }}</div>
    @else
        <p class="text-gray-500">{{ __('market.no_results') }}</p>
    @endif
</div>
@endsection
