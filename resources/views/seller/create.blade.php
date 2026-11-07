<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Create your store</h2></x-slot>
    <div class="py-10"><div class="max-w-xl mx-auto px-4 sm:px-6">
        <form method="POST" action="{{ route('store.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5"
              x-data="{ s: '{{ old('slug') }}', msg: '', ok: null, t: null,
                check() { clearTimeout(this.t); this.s = this.s.toLowerCase(); if (this.s.length < 3) { this.msg=''; this.ok=null; return }
                this.t = setTimeout(async () => { const r = await fetch('{{ route('store.check') }}?s=' + encodeURIComponent(this.s), {headers:{Accept:'application/json'}}); const j = await r.json(); this.ok = j.ok; this.msg = j.message }, 350) } }">
            @csrf
            <div>
                <label class="block font-medium text-sm text-gray-700">Store name</label>
                <input name="name" value="{{ old('name') }}" required maxlength="80" class="mt-1 border-gray-300 rounded-md shadow-sm w-full" placeholder="My Fashion House">
                @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-medium text-sm text-gray-700">Store address</label>
                <div class="mt-1 flex items-center flex-wrap gap-1">
                    <input name="slug" x-model="s" @input="check()" maxlength="30" required autocomplete="off" class="border-gray-300 rounded-md shadow-sm w-44" placeholder="myfashion">
                    <span class="text-gray-600">.{{ config('shop.base_domain') }}</span>
                </div>
                <p class="mt-1 text-sm" :class="ok ? 'text-green-600' : 'text-red-600'" x-text="msg"></p>
                @error('slug')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-primary-button>Create my store (1 click)</x-primary-button>
            <p class="text-xs text-gray-500">We add Cash on Delivery and 3 sample products so you can start right away.</p>
        </form>
    </div></div>
</x-app-layout>
