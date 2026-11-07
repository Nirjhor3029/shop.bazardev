@extends('storefront.layout')
@section('title', 'Checkout - '.$store->name)
@section('content')
<h1 class="text-2xl font-bold mb-4">Checkout</h1>
<div class="grid md:grid-cols-2 gap-6" x-data="{ pm: '{{ old('payment_method', $methods->first()?->type) }}' }">
<form method="POST" action="/checkout" class="sf-card p-5 space-y-4">@csrf
    @error('cart')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    @foreach ([['name','Your name','text'],['phone','Mobile number (01XXXXXXXXX)','tel'],['email','Email (optional)','email']] as [$f,$l,$t])
        <div><label class="block text-sm font-medium">{{ $l }}</label><input type="{{ $t }}" name="{{ $f }}" value="{{ old($f) }}" @if ($f !== 'email') required @endif class="mt-1 border-gray-300 rounded-md w-full">@error($f)<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
    @endforeach
    <div><label class="block text-sm font-medium">Full address</label><textarea name="address" rows="3" required class="mt-1 border-gray-300 rounded-md w-full">{{ old('address') }}</textarea>@error('address')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label class="block text-sm font-medium">Note (optional)</label><input name="note" value="{{ old('note') }}" class="mt-1 border-gray-300 rounded-md w-full"></div>
    <div>
        <p class="text-sm font-medium mb-2">Payment</p>
        @foreach ($methods as $m)
            <label class="block border rounded-md p-3 mb-2 cursor-pointer">
                <input type="radio" name="payment_method" value="{{ $m->type }}" x-model="pm" class="mr-2">
                <span class="font-medium">{{ $m->type === 'cod' ? 'Cash on delivery' : ucfirst($m->type).' (send money)' }}</span>
                <span class="block text-sm text-gray-600 ml-6" x-show="pm === '{{ $m->type }}'">
                    @if ($m->type === 'cod'){{ $m->instructions ?: 'Pay in cash when you receive the order.' }}
                    @else Send <b>{{ $cur }} {{ number_format($subtotal + $delivery) }}</b> to <b>{{ $m->number }}</b> (Send Money), then enter the details below. {{ $m->instructions }}@endif
                </span>
            </label>
        @endforeach
        @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <div x-show="pm !== 'cod'" class="space-y-3 mt-3">
            <div><label class="block text-sm font-medium">Transaction ID</label><input name="trx_id" value="{{ old('trx_id') }}" maxlength="40" class="mt-1 border-gray-300 rounded-md w-full">@error('trx_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium">The number you sent from</label><input name="payer_number" value="{{ old('payer_number') }}" class="mt-1 border-gray-300 rounded-md w-full">@error('payer_number')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>
    </div>
    <button class="text-white px-6 py-3 rounded-md w-full" style="background: {{ $color }}">Place order</button>
</form>
<div class="sf-card p-5 h-fit">
    <h2 class="font-semibold mb-3">Order summary</h2>
    @foreach ($lines as $l)<div class="flex justify-between text-sm py-1"><span>{{ $l['product']->name }} × {{ $l['qty'] }}</span><span>{{ number_format($l['total']) }}</span></div>@endforeach
    <hr class="my-3">
    <div class="flex justify-between text-sm"><span>Subtotal</span><span>{{ number_format($subtotal) }}</span></div>
    <div class="flex justify-between text-sm"><span>Delivery</span><span>{{ $delivery > 0 ? number_format($delivery) : 'Free' }}</span></div>
    <div class="flex justify-between font-semibold mt-2"><span>Total</span><span>{{ $cur }} {{ number_format($subtotal + $delivery) }}</span></div>
</div></div>
@endsection
