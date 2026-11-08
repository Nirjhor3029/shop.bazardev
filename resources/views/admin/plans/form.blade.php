@php $editing = $plan->exists; @endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit plan' : 'New plan' }}</h2></x-slot>
    <div class="py-8"><div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ $editing ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4" x-data="{ allTemplates: {{ old('all_templates', $plan->landing_templates === null) ? 'true' : 'false' }}, allThemes: {{ old('all_themes', $plan->storefront_themes === null) ? 'true' : 'false' }}, allProductTemplates: {{ old('all_product_templates', $plan->product_templates === null) ? 'true' : 'false' }} }">
            @csrf @if ($editing) @method('PUT') @endif
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Name</label><input name="name" value="{{ old('name', $plan->name) }}" required class="mt-1 border-gray-300 rounded-md w-full">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium">Slug</label><input name="slug" value="{{ old('slug', $plan->slug) }}" required class="mt-1 border-gray-300 rounded-md w-full">@error('slug')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="block text-sm font-medium">Price per month (৳, 0 for free)</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $plan->price ?? 0) }}" required class="mt-1 border-gray-300 rounded-md w-full">@error('price')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium">Max products (blank = unlimited)</label><input type="number" min="0" name="max_products" value="{{ old('max_products', $plan->max_products) }}" class="mt-1 border-gray-300 rounded-md w-full"></div>
                <div><label class="block text-sm font-medium">Max landing pages (blank = unlimited)</label><input type="number" min="0" name="max_landing_pages" value="{{ old('max_landing_pages', $plan->max_landing_pages) }}" class="mt-1 border-gray-300 rounded-md w-full"></div>
            </div>

            <div><label class="block text-sm font-medium mb-1">Landing page templates allowed</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="1" x-model="allTemplates" name="all_templates" class="rounded"> All templates (including any added later)</label>
                <div class="mt-2 grid grid-cols-2 gap-1" x-show="!allTemplates">
                    @foreach (\App\Models\LandingPage::TEMPLATES as $k => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="landing_templates[]" value="{{ $k }}" class="rounded" @checked(in_array($k, old('landing_templates', $plan->landing_templates ?? [])))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>

            <div><label class="block text-sm font-medium mb-1">Storefront themes allowed</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="1" x-model="allThemes" name="all_themes" class="rounded"> All themes (including any added later)</label>
                <div class="mt-2 grid grid-cols-2 gap-1" x-show="!allThemes">
                    @foreach (\App\Models\Store::THEMES as $k => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="storefront_themes[]" value="{{ $k }}" class="rounded" @checked(in_array($k, old('storefront_themes', $plan->storefront_themes ?? [])))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>

            <div><label class="block text-sm font-medium mb-1">Product detail page templates allowed</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="1" x-model="allProductTemplates" name="all_product_templates" class="rounded"> All templates (including any added later)</label>
                <div class="mt-2 grid grid-cols-2 gap-1" x-show="!allProductTemplates">
                    @foreach (\App\Models\Store::PRODUCT_TEMPLATES as $k => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="product_templates[]" value="{{ $k }}" class="rounded" @checked(in_array($k, old('product_templates', $plan->product_templates ?? [])))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>

            <div class="border-t pt-4 space-y-2"><h3 class="font-semibold">Feature toggles</h3>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_subdomain" value="1" class="rounded" @checked(old('allow_subdomain', $plan->allow_subdomain))> Branded subdomain ({slug}.{{ config('shop.base_domain') }}) instead of a path URL</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_sslcommerz" value="1" class="rounded" @checked(old('allow_sslcommerz', $plan->allow_sslcommerz))> SSLCommerz real-time payment gateway</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_pixel_tracking" value="1" class="rounded" @checked(old('allow_pixel_tracking', $plan->allow_pixel_tracking))> Facebook Pixel + Conversions API tracking</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_branding" value="1" class="rounded" @checked(old('remove_branding', $plan->remove_branding))> Remove "Made with Bazardev Shop" footer</label>
                <label class="flex items-center gap-2 text-sm text-gray-400"><input type="checkbox" disabled class="rounded"> Custom domain (coming soon)</label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $plan->is_active ?? true))> Active (sellers/admin can pick this plan)</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" class="rounded" @checked(old('is_default', $plan->is_default))> Default (every new signup starts here)</label>
            </div>
            <div><label class="block text-sm font-medium">Sort order (lower shows first)</label><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="mt-1 border-gray-300 rounded-md w-32"></div>

            <div class="flex items-center gap-3"><x-primary-button>{{ $editing ? 'Save' : 'Create' }}</x-primary-button>
                <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-600">Back</a></div>
        </form>
        @if ($editing && ! $plan->is_default && $plan->stores()->count() === 0)
            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" x-data="{ sure: false }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600" x-text="sure ? 'Click again to really delete' : 'Delete plan'"></button>
            </form>
        @endif
    </div></div>
</x-app-layout>
