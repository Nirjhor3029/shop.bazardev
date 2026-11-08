<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppearanceController extends Controller
{
    public function edit()
    {
        $store = app('currentStore');
        $look = $store->look();
        // show saved order first, then any section not in the list yet
        $rows = collect($look['sections'])->filter(fn ($s) => isset(Store::SECTIONS[$s['type']]))->values();
        foreach (array_keys(Store::SECTIONS) as $t) {
            if (! $rows->contains('type', $t)) {
                $rows->push(['type' => $t, 'on' => false]);
            }
        }

        return view('seller.appearance.edit', ['store' => $store, 'look' => $look, 'rows' => $rows, 'plan' => $store->activePlan()]);
    }

    public function update(Request $request, ImageStore $images)
    {
        $store = app('currentStore');
        $data = $request->validate([
            'theme' => ['required', Rule::in(array_keys(Store::THEMES))],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['required', Rule::in(array_keys(Store::FONTS))],
            'announcement' => ['nullable', 'string', 'max:140'],
            'hero_title' => ['nullable', 'string', 'max:80'],
            'hero_text' => ['nullable', 'string', 'max:200'],
            'hero_button' => ['nullable', 'string', 'max:30'],
            'about' => ['nullable', 'string', 'max:1500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:200'],
            'facebook' => ['nullable', 'url:https', 'max:200'],
            'whatsapp' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'lang' => ['nullable', 'in:bn,en'],
            'overlay' => ['nullable', Rule::in(array_keys(Store::OVERLAYS))],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sections' => ['required', 'array'],
            'sections.*.order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);
        if (! $store->activePlan()->allowsTheme($data['theme'])) {
            return back()->withErrors(['theme' => 'This theme is not available on your current plan.'])->withInput();
        }

        $look = $store->look();
        foreach (['logo' => 400, 'banner' => 1600] as $key => $max) {
            if ($request->boolean("remove_$key") || $request->hasFile($key)) {
                if ($look[$key]) {
                    Storage::disk('public')->delete($look[$key]);
                }
                $look[$key] = null;
            }
            if ($request->hasFile($key)) {
                $look[$key] = $images->save($request->file($key), $store->id, 'stores', $max);
            }
        }

        // sections: only known types, ordered by the numbers the seller typed
        $sections = collect(Store::SECTIONS)->keys()
            ->map(fn ($t) => ['type' => $t, 'on' => $request->boolean("sections.$t.on"), 'order' => (int) ($data['sections'][$t]['order'] ?? 50)])
            ->sortBy('order')->map(fn ($s) => ['type' => $s['type'], 'on' => $s['on']])->values()->all();

        $look = array_merge($look, collect($data)->only(['font', 'announcement', 'hero_title', 'hero_text', 'hero_button', 'about', 'phone', 'email', 'address', 'facebook', 'whatsapp'])->map(fn ($v) => $v ?? '')->all() + ['lang' => $data['lang'] ?? $look['lang'], 'overlay' => $data['overlay'] ?? $look['overlay'] ?? 'dark'], ['sections' => $sections, 'digits' => $request->boolean('digits')]);

        $store->update([
            'theme' => $data['theme'],
            'settings' => array_merge($store->settings ?? [], ['color' => $data['color'], 'look' => $look]),
        ]);

        return back()->with('status', 'Appearance saved.');
    }
}
