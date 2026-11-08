@php $editing = $lp->exists; @endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit landing page' : 'New landing page' }}</h2></x-slot>
    @include('seller._nav')
    <div class="py-6"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        @error('limit')<div class="bg-amber-50 text-amber-700 p-3 rounded">{{ $message }}</div>@enderror
        <form method="POST" action="{{ $editing ? route('seller.landing.update', $lp) : route('seller.landing.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4" x-data="{ template: '{{ old('template', $lp->template ?: 'classic') }}' }">
            @csrf @if ($editing) @method('PUT') @endif
            <div><label class="block text-sm font-medium text-gray-700">Product</label>
                <select name="product_id" required class="mt-1 border-gray-300 rounded-md w-full">
                    <option value="">Choose a product</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}" @selected(old('product_id', $lp->product_id) == $p->id)>{{ $p->name }}</option>@endforeach
                </select>@error('product_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @if ($products->isEmpty())<p class="text-sm text-amber-600 mt-1">Add a product first.</p>@endif</div>

            <div><label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                <div class="grid sm:grid-cols-3 gap-3">
                @foreach (\App\Models\LandingPage::TEMPLATES as $k => $label)
                    @php $locked = ! $plan->allowsTemplate($k); @endphp
                    <label class="block border rounded-md p-3 text-sm {{ $locked ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'cursor-pointer' }}" :class="template === '{{ $k }}' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'" @if (! $locked) @click="template = '{{ $k }}'" @endif>
                        <input type="radio" name="template" value="{{ $k }}" class="hidden" x-model="template" @disabled($locked)>
                        {{ $label }} @if ($locked)<span class="text-amber-600 font-medium">(Premium)</span>@endif
                    </label>
                @endforeach
                </div>@error('template')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>

            <div><label class="block text-sm font-medium text-gray-700">Headline</label>
                <input name="headline" maxlength="150" value="{{ old('headline', $lp->headline) }}" placeholder="e.g. Original Leather Wallet - today only 500 taka" class="mt-1 border-gray-300 rounded-md w-full">@error('headline')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-gray-700">Subheadline (optional)</label>
                <input name="subheadline" maxlength="300" value="{{ old('subheadline', $lp->subheadline) }}" placeholder="One line under the headline" class="mt-1 border-gray-300 rounded-md w-full">@error('subheadline')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-gray-700">Benefits (one per line, max 6)</label>
                <textarea name="benefits" rows="5" placeholder="Free delivery&#10;7-day return&#10;Original product guarantee" class="mt-1 border-gray-300 rounded-md w-full">{{ old('benefits', $lp->benefits ? implode("\n", $lp->benefits) : '') }}</textarea></div>

            <div x-show="template === 'video'"><label class="block text-sm font-medium text-gray-700">Demo video URL (YouTube link, or a direct video file link)</label>
                <input name="video_url" type="url" maxlength="300" value="{{ old('video_url', $lp->video_url) }}" placeholder="https://youtube.com/watch?v=..." class="mt-1 border-gray-300 rounded-md w-full">@error('video_url')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>

            <div x-show="template === 'combo'" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Combo options (quantity + bundle price, up to 4)</label>
                @for ($i = 0; $i < 4; $i++)
                    @php $row = old('combo.'.$i) ?? ($lp->combos[$i] ?? []); @endphp
                    <div class="flex gap-2">
                        <input type="number" min="1" max="99" name="combo[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" placeholder="Qty" class="border-gray-300 rounded-md w-20">
                        <input type="number" min="0" step="0.01" name="combo[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" placeholder="Price" class="border-gray-300 rounded-md w-28">
                        <input type="text" maxlength="40" name="combo[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Label (optional, e.g. Popular)" class="border-gray-300 rounded-md flex-1">
                    </div>
                @endfor
                <p class="text-xs text-gray-500">Leave a row's quantity blank to skip it.</p>
            </div>

            <div class="border-t pt-4 space-y-3"><h3 class="font-semibold">Facebook tracking (optional)</h3>
                @if (! $plan->allow_pixel_tracking)
                    <p class="text-sm text-amber-600">Not available on your current plan. Upgrade to track Facebook ad conversions.</p>
                @else
                <p class="text-xs text-gray-500">Paste your own Meta Pixel ID and Conversions API token (from your Business Manager) to track ad conversions on this page.</p>
                <div><label class="block text-sm font-medium text-gray-700">Pixel ID</label>
                    <input name="pixel_id" value="{{ old('pixel_id', $lp->pixel_id) }}" maxlength="30" placeholder="e.g. 1234567890123456" class="mt-1 border-gray-300 rounded-md w-full">@error('pixel_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700">Conversions API access token</label>
                    <input name="capi_token" type="password" maxlength="500" placeholder="{{ $editing && $lp->capi_token ? 'Saved - leave blank to keep it' : 'Paste your access token' }}" class="mt-1 border-gray-300 rounded-md w-full">@error('capi_token')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                @endif
            </div>

            <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $lp->is_published)) class="rounded"> Published (page is live)</label>
            @if ($editing && $lp->is_published)<p class="text-sm text-gray-500">Link: <a href="{{ $lp->url() }}" target="_blank" rel="noopener" class="text-indigo-600 underline break-all">{{ $lp->url() }}</a></p>@endif
            <div class="flex items-center gap-3"><x-primary-button>{{ $editing ? 'Save' : 'Create' }}</x-primary-button>
                <a href="{{ route('seller.landing.index') }}" class="text-sm text-gray-600">Back</a></div>
        </form>
        @if ($editing)
            <form method="POST" action="{{ route('seller.landing.destroy', $lp) }}" x-data="{ sure: false }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600" x-text="sure ? 'Click again to really delete' : 'Delete landing page'"></button>
            </form>
        @endif
    </div></div>
</x-app-layout>
