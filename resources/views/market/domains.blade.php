@extends('layouts.market')
@section('title', __('market.domains_title').' · '.__('market.brand'))
@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl md:text-3xl font-bold">{{ __('market.domains_title') }}</h1>
    <p class="mt-2 text-gray-600">{{ __('market.domains_sub') }}</p>

    <form method="POST" action="{{ route('domains.check') }}" class="mt-6 flex gap-2">
        @csrf
        <input type="text" name="domain" value="{{ old('domain') }}" required maxlength="255" placeholder="{{ __('market.domains_ph') }}" class="flex-1 rounded-lg border-gray-300" autofocus>
        <button class="bg-brand hover:bg-brand-dark text-white font-semibold rounded-lg px-5">{{ __('market.domains_btn') }}</button>
    </form>
    @error('domain')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    @if (session('domainResult'))
        @php $r = session('domainResult'); @endphp
        <div class="mt-5 rounded-lg border p-4
            @if ($r['status'] === 'available') bg-green-50 border-green-200
            @elseif ($r['status'] === 'taken') bg-red-50 border-red-200
            @else bg-gray-50 border-gray-200 @endif">
            <p class="font-semibold">{{ $r['domain'] }}</p>
            <p class="mt-1 text-sm">
                @if ($r['status'] === 'available') <span class="text-green-700">{{ __('market.domains_available') }}</span>
                @elseif ($r['status'] === 'taken') <span class="text-red-700">{{ __('market.domains_taken') }}</span>
                @elseif ($r['status'] === 'invalid') <span class="text-gray-600">{{ __('market.domains_invalid') }}</span>
                @else <span class="text-gray-600">{{ __('market.domains_unknown') }}</span>
                @endif
            </p>
            @if ($r['status'] === 'available')
                <p class="mt-2 text-sm text-gray-600">{{ __('market.domains_order_hint') }}</p>
            @endif
        </div>

        @if (session('domainAlternatives'))
            <div class="mt-6">
                <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('market.domains_other_ext') }}</p>
                <div class="rounded-lg border divide-y overflow-hidden">
                    @foreach (session('domainAlternatives') as $alt)
                        <div class="px-3 py-2 text-sm flex items-center justify-between gap-2
                            @if ($alt['status'] === 'available') bg-green-50
                            @elseif ($alt['status'] === 'taken') bg-red-50
                            @else bg-gray-50 @endif">
                            <span>{{ $alt['domain'] }}</span>
                            @if ($alt['status'] === 'available')<span class="text-green-700 text-xs font-semibold shrink-0">{{ __('market.domains_free') }}</span>
                            @elseif ($alt['status'] === 'taken')<span class="text-red-700 text-xs shrink-0">{{ __('market.domains_taken_short') }}</span>
                            @else <span class="text-gray-500 text-xs shrink-0">?</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
