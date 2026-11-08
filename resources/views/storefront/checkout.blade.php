@extends('storefront.layout')
@section('title', __('store.checkout').' - '.$store->name)
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ __('store.checkout') }}</h1>
<div class="grid md:grid-cols-2 gap-6" x-data="{ pm: '{{ old('payment_method', $methods->first()?->type) }}' }">
<form method="POST" action="{{ $store->storePath('/checkout') }}" class="sf-card p-5 space-y-4 order-2 md:order-1">@csrf
    @error('cart')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    @foreach ([['name','name','text','name'],['phone','mobile','tel','tel'],['email','email_opt','email','email']] as [$f,$l,$t,$ac])
        <div><label class="block text-sm font-medium" for="f-{{ $f }}">{{ __('store.'.$l) }}</label><input id="f-{{ $f }}" type="{{ $t }}" name="{{ $f }}" value="{{ old($f) }}" autocomplete="{{ $ac }}" @if ($f !== 'email') required @endif class="mt-1 border-gray-300 rounded-lg w-full">@error($f)<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
    @endforeach
    <div><label class="block text-sm font-medium" for="f-address">{{ __('store.full_address') }}</label><textarea id="f-address" name="address" rows="3" required autocomplete="street-address" class="mt-1 border-gray-300 rounded-lg w-full">{{ old('address') }}</textarea>@error('address')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label class="block text-sm font-medium" for="f-note">{{ __('store.note_opt') }}</label><input id="f-note" name="note" value="{{ old('note') }}" class="mt-1 border-gray-300 rounded-lg w-full"></div>
    <div>
        <p class="text-sm font-medium mb-2">{{ __('store.pay') }}</p>
        @foreach ($methods as $m)
            <label class="block border rounded-lg p-3 mb-2 cursor-pointer">
                <input type="radio" name="payment_method" value="{{ $m->type }}" x-model="pm" class="mr-2">
                <span class="font-medium">{{ __('store.'.$m->type) }}</span>
                <span class="block text-sm text-gray-600 ml-6" x-show="pm === '{{ $m->type }}'">
                    @if ($m->type === 'cod'){{ $m->instructions ?: __('store.pay_cod_note', ['amount' => \App\Support\Fmt::money($subtotal + $delivery)]) }}
                    @elseif ($m->type === 'sslcommerz'){{ __('store.pay_gateway_note') }}
                    @else{{ __('store.send_money', ['amount' => \App\Support\Fmt::money($subtotal + $delivery), 'number' => \App\Support\Fmt::digits($m->number)]) }} {{ $m->instructions }}@endif
                </span>
            </label>
        @endforeach
        @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <div x-show="pm !== 'cod' && pm !== 'sslcommerz'" class="space-y-3 mt-3">
            <div><label class="block text-sm font-medium" for="f-trx">{{ __('store.trx_id') }}</label><input id="f-trx" name="trx_id" value="{{ old('trx_id') }}" maxlength="40" class="mt-1 border-gray-300 rounded-lg w-full">@error('trx_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium" for="f-payer">{{ __('store.sent_from') }}</label><input id="f-payer" name="payer_number" value="{{ old('payer_number') }}" class="mt-1 border-gray-300 rounded-lg w-full">@error('payer_number')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>
    </div>
    <button class="sf-btn w-full">{{ __('store.place_order') }}</button>
</form>
<div class="sf-card p-5 h-fit order-1 md:order-2">
    <h2 class="font-semibold mb-3">{{ __('store.summary') }}</h2>
    @foreach ($lines as $l)<div class="flex justify-between text-sm py-1"><span>{{ $l['product']->name }}{{ $l['variant'] ? ' ('.$l['variant']->label().')' : '' }} × {{ \App\Support\Fmt::digits($l['qty']) }}</span><span>{{ \App\Support\Fmt::money($l['total']) }}</span></div>@endforeach
    <hr class="my-3">
    <div class="flex justify-between text-sm"><span>{{ __('store.subtotal') }}</span><span>{{ \App\Support\Fmt::money($subtotal) }}</span></div>
    <div class="flex justify-between text-sm"><span>{{ __('store.delivery') }}</span><span>{{ $delivery > 0 ? \App\Support\Fmt::money($delivery) : __('store.free') }}</span></div>
    <div class="flex justify-between font-semibold mt-2"><span>{{ __('store.total') }}</span><span class="sf-price">{{ \App\Support\Fmt::money($subtotal + $delivery) }}</span></div>
</div></div>
@endsection
