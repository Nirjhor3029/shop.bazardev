@extends('storefront.layout')
@section('title', __('store.order').' '.$order->number.' - '.$store->name)
@php $conversionConfirmed = $order->payment_method !== 'sslcommerz' || $order->payment_status === 'paid'; @endphp
@if ($order->landingPage?->pixel_id && $conversionConfirmed)
@section('head')
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $order->landingPage->pixel_id }}');
fbq('track', 'Purchase', {value: {{ (float) $order->total }}, currency: {{ \Illuminate\Support\Js::from($order->currency) }}}, {eventID: {{ \Illuminate\Support\Js::from($order->token) }}});
</script>
@endsection
@endif
@section('content')
@php $amount = \App\Support\Fmt::money($order->total); @endphp
<div class="sf-card p-6 max-w-2xl">
    <h1 class="text-2xl font-bold">{{ __('store.thanks', ['name' => $order->customer_name]) }}</h1>
    <p class="mt-1 text-gray-600">{{ __('store.order') }} <b>{{ \App\Support\Fmt::digits($order->number) }}</b> · {{ __('store.status') }}: <b>{{ __('store.st.'.$order->status) }}</b></p>
    @if ($order->payment_method === 'cod')<p class="mt-3 text-sm">{{ __('store.pay_cod_note', ['amount' => $amount]) }}</p>
    @elseif ($order->payment_method === 'sslcommerz')<p class="mt-3 text-sm">{{ $order->payment_status === 'paid' ? __('store.pay_gateway_paid') : __('store.pay_gateway_unpaid') }}</p>
    @else<p class="mt-3 text-sm">{{ __('store.pay_manual_note', ['method' => __('store.'.$order->payment_method), 'trx' => $order->trx_id, 'state' => __('store.pst.'.$order->payment_status)]) }}</p>@endif
    <div class="mt-5 divide-y">@foreach ($order->items as $i)<div class="py-2 flex justify-between text-sm"><span>{{ $i->name }}{{ $i->variant_label ? ' ('.$i->variant_label.')' : '' }} × {{ \App\Support\Fmt::digits($i->quantity) }}</span><span>{{ \App\Support\Fmt::money($i->line_total) }}</span></div>@endforeach</div>
    <div class="mt-3 text-sm space-y-1"><div class="flex justify-between"><span>{{ __('store.delivery') }}</span><span>{{ \App\Support\Fmt::money($order->delivery_charge) }}</span></div>
        <div class="flex justify-between font-semibold"><span>{{ __('store.total') }}</span><span>{{ $amount }}</span></div></div>
    <p class="mt-5 text-xs text-gray-500">{{ __('store.deliver_to') }}: {{ $order->address }}<br>{{ __('store.save_link') }}</p>
</div>
@endsection
