@extends('storefront.layout')
@section('content')
    @foreach ($store->sections() as $section)
        <div class="sf-reveal">@include('storefront.sections.'.$section)</div>
    @endforeach
@endsection
