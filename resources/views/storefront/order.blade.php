@extends('storefront.layout')
@section('title', 'Order '.$order->number.' - '.$store->name)
@section('content')
<div class="sf-card p-6 max-w-2xl">
    <h1 class="text-2xl font-bold">Thank you, {{ $order->customer_name }}!</h1>
    <p class="mt-1 text-gray-600">Order <b>{{ $order->number }}</b> · status: <b>{{ $order->status }}</b></p>
    @if ($order->payment_method === 'cod')<p class="mt-3 text-sm">Pay <b>{{ $order->currency }} {{ number_format($order->total) }}</b> in cash when you receive the order.</p>
    @else<p class="mt-3 text-sm">Payment via {{ ucfirst($order->payment_method) }} (TrxID {{ $order->trx_id }}): <b>{{ $order->payment_status === 'verifying' ? 'the seller will check it soon' : $order->payment_status }}</b>.</p>@endif
    <div class="mt-5 divide-y">@foreach ($order->items as $i)<div class="py-2 flex justify-between text-sm"><span>{{ $i->name }} × {{ $i->quantity }}</span><span>{{ number_format($i->line_total) }}</span></div>@endforeach</div>
    <div class="mt-3 text-sm space-y-1"><div class="flex justify-between"><span>Delivery</span><span>{{ number_format($order->delivery_charge) }}</span></div>
        <div class="flex justify-between font-semibold"><span>Total</span><span>{{ $order->currency }} {{ number_format($order->total) }}</span></div></div>
    <p class="mt-5 text-xs text-gray-500">Deliver to: {{ $order->address }}<br>Save this page link to check your order later.</p>
</div>
@endsection
