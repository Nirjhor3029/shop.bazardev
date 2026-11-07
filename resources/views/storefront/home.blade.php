@extends('storefront.layout')
@section('content')
    @foreach ($store->sections() as $section)
        @include('storefront.sections.'.$section)
    @endforeach
@endsection
