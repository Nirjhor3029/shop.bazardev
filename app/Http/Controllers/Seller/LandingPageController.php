<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LandingPageController extends Controller
{
    public function index()
    {
        return view('seller.landing.index', ['pages' => LandingPage::with('product')->latest()->get(), 'plan' => app('currentStore')->activePlan()]);
    }

    public function create()
    {
        return view('seller.landing.form', ['lp' => new LandingPage(['template' => 'classic']), 'products' => Product::where('is_active', true)->orderBy('name')->get(), 'plan' => app('currentStore')->activePlan()]);
    }

    public function store(Request $request)
    {
        $limit = app('currentStore')->activePlan()->max_landing_pages;
        if ($limit !== null && LandingPage::count() >= $limit) {
            return back()->withErrors(['limit' => "Your plan allows up to {$limit} landing page(s). Upgrade for more."])->withInput();
        }

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['headline'] ?? '' ?: 'offer');
        LandingPage::create($data);

        return redirect()->route('seller.landing.index')->with('status', 'Landing page created.');
    }

    public function edit(LandingPage $landing)
    {
        return view('seller.landing.form', ['lp' => $landing, 'products' => Product::where('is_active', true)->orderBy('name')->get(), 'plan' => app('currentStore')->activePlan()]);
    }

    public function update(Request $request, LandingPage $landing)
    {
        $landing->update($this->validated($request));

        return back()->with('status', 'Saved.');
    }

    public function destroy(LandingPage $landing)
    {
        $landing->delete();

        return redirect()->route('seller.landing.index')->with('status', 'Landing page deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', function ($attr, $value, $fail) {
                if (! Product::whereKey($value)->exists()) {
                    $fail('Choose one of your own products.');
                }
            }],
            'template' => ['required', Rule::in(array_keys(LandingPage::TEMPLATES))],
            'headline' => ['nullable', 'string', 'max:150'],
            'subheadline' => ['nullable', 'string', 'max:300'],
            'benefits' => ['nullable', 'string', 'max:1000'],
            'video_url' => ['nullable', 'url', 'max:300'],
            'combo' => ['nullable', 'array', 'max:4'],
            'combo.*.qty' => ['nullable', 'integer', 'min:1', 'max:99'],
            'combo.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'combo.*.label' => ['nullable', 'string', 'max:40'],
            'pixel_id' => ['nullable', 'string', 'max:30', 'regex:/^\d+$/'],
            'capi_token' => ['nullable', 'string', 'max:500'],
        ], ['pixel_id.regex' => 'The Pixel ID should be digits only.']);
        $data['benefits'] = collect(preg_split('/\r?\n/', (string) $request->input('benefits')))->map('trim')->filter()->take(6)->values()->all();
        $data['combos'] = collect($request->input('combo', []))
            ->filter(fn ($row) => filled($row['qty'] ?? null) && ($row['price'] ?? '') !== '')
            ->map(fn ($row) => ['qty' => (int) $row['qty'], 'price' => (float) $row['price'], 'label' => trim($row['label'] ?? '') ?: null])
            ->sortBy('qty')->values()->all() ?: null;
        $data['is_published'] = $request->boolean('is_published');
        unset($data['combo']);
        if (blank($data['capi_token'] ?? null)) {
            unset($data['capi_token']); // never blank out an already-saved token just because the (masked) field was left empty
        }

        $plan = app('currentStore')->activePlan();
        if (! $plan->allowsTemplate($data['template'])) {
            throw ValidationException::withMessages(['template' => 'This template is not available on your current plan.']);
        }
        if (! $plan->allow_pixel_tracking && (filled($data['pixel_id'] ?? null) || filled($data['capi_token'] ?? null))) {
            throw ValidationException::withMessages(['pixel_id' => 'Facebook tracking is not available on your current plan.']);
        }

        return $data;
    }

    private function uniqueSlug(string $headline): string
    {
        $base = Str::slug($headline) ?: 'offer';
        $slug = $base;
        $i = 2;
        while (LandingPage::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
