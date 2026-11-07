<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Order {{ $order->number }}</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm space-y-1">
            <p><b>{{ $order->customer_name }}</b> · {{ $order->customer_phone }} @if ($order->customer_email)· {{ $order->customer_email }}@endif</p>
            <p class="whitespace-pre-line">{{ $order->address }}</p>
            @if ($order->note)<p class="text-gray-600">Note: {{ $order->note }}</p>@endif
            <p class="pt-2">Status: <b>{{ $order->status }}</b> · Payment: <b>{{ strtoupper($order->payment_method) }}</b> ({{ $order->payment_status }})
                @if ($order->trx_id) · TrxID <b>{{ $order->trx_id }}</b> from {{ $order->payer_number }}@endif</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
            @foreach ($order->items as $i)<div class="flex justify-between py-1"><span>{{ $i->name }} × {{ $i->quantity }}</span><span>{{ number_format($i->line_total) }}</span></div>@endforeach
            <hr class="my-2"><div class="flex justify-between"><span>Delivery</span><span>{{ number_format($order->delivery_charge) }}</span></div>
            <div class="flex justify-between font-semibold"><span>Total</span><span>{{ $order->currency }} {{ number_format($order->total) }}</span></div>
        </div>
        @if ($order->nextStatuses())
            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3">
                @foreach ($order->nextStatuses() as $to)
                    <form method="POST" action="{{ route('seller.orders.status', $order) }}" x-data="{ sure: {{ $to === 'cancelled' ? 'false' : 'true' }} }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
                        @csrf <input type="hidden" name="status" value="{{ $to }}">
                        <button class="px-4 py-2 rounded-md text-sm {{ $to === 'cancelled' ? 'border border-red-300 text-red-700' : 'bg-indigo-600 text-white' }}" x-text="sure && '{{ $to }}' === 'cancelled' && $el.dataset.armed ? 'Click again to cancel' : '{{ ucfirst($to) }}'" data-armed=""></button>
                    </form>
                @endforeach
            </div>
        @endif
        @if ($order->status !== 'cancelled')
            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3 items-center text-sm"><span>Payment:</span>
                @foreach (['paid' => 'Mark paid', 'failed' => 'Mark failed', 'unpaid' => 'Mark unpaid'] as $v => $l)
                    @if ($order->payment_status !== $v)<form method="POST" action="{{ route('seller.orders.payment', $order) }}">@csrf<input type="hidden" name="payment_status" value="{{ $v }}"><button class="px-3 py-1 border rounded-md">{{ $l }}</button></form>@endif
                @endforeach
            </div>
        @endif
    </div></div>
</x-app-layout>
