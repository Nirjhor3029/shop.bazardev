@if ($specs->isNotEmpty())
<div class="mt-5" id="sf-specs">
    <h2 class="font-semibold mb-2">{{ __('store.specs') }}</h2>
    <table class="w-full text-sm">
        <tbody>
            @foreach ($specs as $row)
                <tr class="border-b border-gray-100 last:border-0">
                    <td class="py-1.5 pr-4 text-gray-500 align-top w-2/5">{{ $row['key'] }}</td>
                    <td class="py-1.5 text-gray-800">{{ $row['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@if ($sizeGuide)
<div class="mt-3" x-data="{ showGuide: false }">
    <button type="button" class="text-sm underline text-gray-600" @click="showGuide = true">{{ __('store.size_guide') }}</button>
    <div x-show="showGuide" @keydown.escape.window="showGuide = false" class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/50 p-4" @click.self="showGuide = false">
        <div class="bg-white rounded-2xl md:rounded-lg p-5 max-w-md w-full max-h-[80vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ __('store.size_guide') }}">
            <div class="flex items-center justify-between mb-3"><h3 class="font-semibold">{{ __('store.size_guide') }}</h3>
                <button type="button" class="text-gray-400 text-xl leading-none" @click="showGuide = false" aria-label="{{ __('store.close') }}">&times;</button></div>
            <p class="whitespace-pre-line text-sm text-gray-700">{{ $sizeGuide }}</p>
        </div>
    </div>
</div>
@endif
