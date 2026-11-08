<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        return view('seller.landing.index', ['pages' => LandingPage::with('product')->latest()->get()]);
    }

    public function create()
    {
        return view('seller.landing.form', ['lp' => new LandingPage(['template' => 'classic']), 'products' => Product::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['headline'] ?: 'offer');
        LandingPage::create($data);

        return redirect()->route('seller.landing.index')->with('status', 'Landing page created.');
    }

    public function edit(LandingPage $landing)
    {
        return view('seller.landing.form', ['lp' => $landing, 'products' => Product::where('is_active', true)->orderBy('name')->get()]);
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
            'headline' => ['nullable', 'string', 'max:150'],
            'subheadline' => ['nullable', 'string', 'max:300'],
            'benefits' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['benefits'] = collect(preg_split('/\r?\n/', (string) $request->input('benefits')))->map('trim')->filter()->take(6)->values()->all();
        $data['is_published'] = $request->boolean('is_published');
        $data['template'] = 'classic';

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
