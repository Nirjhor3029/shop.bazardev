<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Appearance</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <p class="text-sm text-gray-500">Preview your store: <a class="text-indigo-600 underline" href="{{ $store->url() }}" target="_blank" rel="noopener">{{ $store->url() }}</a> @if ($store->status !== 'published')(publish it first)@endif</p>
        <form method="POST" enctype="multipart/form-data" action="{{ route('seller.appearance.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">@csrf
            <div x-data="{ theme: '{{ old('theme', $store->theme) }}' }">
                <p class="text-sm font-medium mb-2">Theme</p>
                <div class="grid sm:grid-cols-3 gap-3">
                @foreach (\App\Models\Store::THEMES as $k => $label)
                    <label class="block border rounded-md p-3 cursor-pointer" :class="theme === '{{ $k }}' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'" @click="theme = '{{ $k }}'">
                        <input type="radio" name="theme" value="{{ $k }}" class="hidden" x-model="theme">
                        @if ($k === 'minimal')
                            <div class="h-14 rounded-lg bg-white border flex items-center px-2 gap-1"><span class="w-6 h-6 rounded-full bg-indigo-500"></span><span class="flex-1 h-2 rounded-full bg-gray-200"></span></div>
                        @elseif ($k === 'bold')
                            <div class="h-14 rounded bg-slate-900 flex items-center px-2 gap-1"><span class="w-6 h-2 bg-indigo-400"></span><span class="flex-1 h-2 bg-slate-600"></span></div>
                        @else
                            <div class="h-14 rounded-sm bg-[#faf6f2] border border-[#e7e0d8] flex items-center px-2"><span class="font-serif text-[#78716c] text-xs">Aa</span></div>
                        @endif
                        <p class="mt-2 text-sm font-medium">{{ $label }}</p>
                    </label>
                @endforeach
                </div>
            @error('theme')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Main color</label><input type="color" name="color" value="{{ old('color', $store->settings['color'] ?? '#4f46e5') }}" class="mt-1 h-10 w-20">@error('color')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium">Font</label><select name="font" class="mt-1 border-gray-300 rounded-md">@foreach (\App\Models\Store::FONTS as $k => $l)<option value="{{ $k }}" @selected(old('font', $look['font']) === $k)>{{ $l }}</option>@endforeach</select></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Logo (png/jpg/webp, 2 MB)</label>
                    @if ($look['logo'])<img src="{{ $store->fileUrl($look['logo']) }}" class="h-12 my-2 bg-gray-100 rounded"><label class="text-xs"><input type="checkbox" name="remove_logo" value="1"> remove</label>@endif
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="mt-1 text-sm">@error('logo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium">Banner image (wide, 2 MB)</label>
                    @if ($look['banner'])<img src="{{ $store->fileUrl($look['banner']) }}" class="h-12 my-2 rounded"><label class="text-xs"><input type="checkbox" name="remove_banner" value="1"> remove</label>@endif
                    <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" class="mt-1 text-sm">@error('banner')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Store language</label><select name="lang" class="mt-1 border-gray-300 rounded-md w-full"><option value="bn" @selected(old('lang', $look['lang']) === 'bn')>বাংলা</option><option value="en" @selected(old('lang', $look['lang']) === 'en')>English</option></select></div>
                <div><label class="block text-sm font-medium">WhatsApp number (customers can order here)</label><input name="whatsapp" value="{{ old('whatsapp', $look['whatsapp']) }}" placeholder="01XXXXXXXXX" class="mt-1 border-gray-300 rounded-md w-full">@error('whatsapp')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="digits" value="1" class="rounded" @checked(old('digits', $look['digits']))> Show prices in Bangla digits (৳১,৫০০)</label>
            <div><label class="block text-sm font-medium">Announcement bar (optional)</label><input name="announcement" maxlength="140" value="{{ old('announcement', $look['announcement']) }}" placeholder="Free delivery over 2000!" class="mt-1 border-gray-300 rounded-md w-full"></div>
            <div class="border-t pt-4 space-y-3"><h3 class="font-semibold">Banner text</h3>
                <input name="hero_title" maxlength="80" value="{{ old('hero_title', $look['hero_title']) }}" placeholder="Big title (default: store name)" class="border-gray-300 rounded-md w-full">
                <input name="hero_text" maxlength="200" value="{{ old('hero_text', $look['hero_text']) }}" placeholder="One line under the title" class="border-gray-300 rounded-md w-full">
                <input name="hero_button" maxlength="30" value="{{ old('hero_button', $look['hero_button']) }}" placeholder="Button text" class="border-gray-300 rounded-md w-full"></div>
            <div class="border-t pt-4 space-y-3"><h3 class="font-semibold">About and contact</h3>
                <textarea name="about" rows="4" maxlength="1500" placeholder="Tell customers about your shop" class="border-gray-300 rounded-md w-full">{{ old('about', $look['about']) }}</textarea>
                <div class="grid sm:grid-cols-2 gap-3">
                    <input name="phone" value="{{ old('phone', $look['phone']) }}" placeholder="Phone" class="border-gray-300 rounded-md">
                    <input name="email" value="{{ old('email', $look['email']) }}" placeholder="Email" class="border-gray-300 rounded-md">
                    <input name="address" value="{{ old('address', $look['address']) }}" placeholder="Address" class="border-gray-300 rounded-md">
                    <input name="facebook" value="{{ old('facebook', $look['facebook']) }}" placeholder="https://facebook.com/yourpage" class="border-gray-300 rounded-md">
                </div>@error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror @error('facebook')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="border-t pt-4"><h3 class="font-semibold">Page sections</h3><p class="text-xs text-gray-500">Tick to show. Lower number = higher on the page.</p>
                @foreach ($rows as $i => $r)
                    <div class="mt-2 flex items-center gap-3 border rounded-md p-2">
                        <input type="number" min="0" max="99" name="sections[{{ $r['type'] }}][order]" value="{{ ($i + 1) * 10 }}" class="w-16 border-gray-300 rounded-md">
                        <label class="flex items-center gap-2"><input type="checkbox" name="sections[{{ $r['type'] }}][on]" value="1" class="rounded" @checked($r['on'])> {{ \App\Models\Store::SECTIONS[$r['type']] }}</label>
                    </div>
                @endforeach</div>
            <x-primary-button>Save appearance</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
