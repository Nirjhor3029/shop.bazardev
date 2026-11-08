<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Plan;
use App\Models\Store;
use Illuminate\Http\Request;

/** Super admin fully configures plans: price, limits, and which templates/themes/features each one unlocks. */
class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => new Plan]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $plan = Plan::create($data);
        $this->enforceSingleDefault($plan);

        return redirect()->route('admin.plans.index')->with('status', 'Plan created.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', ['plan' => $plan]);
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request));
        $this->enforceSingleDefault($plan);

        return back()->with('status', 'Saved.');
    }

    /** Exactly one plan may be is_default (it's what new signups and expired subscriptions fall back to). */
    private function enforceSingleDefault(Plan $plan): void
    {
        if ($plan->is_default) {
            Plan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        } elseif (! Plan::where('is_default', true)->exists()) {
            $plan->update(['is_default' => true]); // never leave the platform with zero default plans
        }
    }

    public function destroy(Plan $plan)
    {
        abort_if($plan->is_default, 422, 'The default plan cannot be deleted.');
        abort_if($plan->stores()->exists(), 422, 'Move stores off this plan before deleting it.');
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plan deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/', 'unique:plans,slug,'.($request->route('plan')?->id ?? 'NULL')],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_landing_pages' => ['nullable', 'integer', 'min:0'],
            'landing_templates' => ['nullable', 'array'],
            'landing_templates.*' => ['string', 'in:'.implode(',', array_keys(LandingPage::TEMPLATES))],
            'storefront_themes' => ['nullable', 'array'],
            'storefront_themes.*' => ['string', 'in:'.implode(',', array_keys(Store::THEMES))],
            'product_templates' => ['nullable', 'array'],
            'product_templates.*' => ['string', 'in:'.implode(',', array_keys(Store::PRODUCT_TEMPLATES))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
        $data['landing_templates'] = $request->boolean('all_templates') ? null : ($data['landing_templates'] ?? []);
        $data['storefront_themes'] = $request->boolean('all_themes') ? null : ($data['storefront_themes'] ?? []);
        $data['product_templates'] = $request->boolean('all_product_templates') ? null : ($data['product_templates'] ?? []);
        $data['allow_subdomain'] = $request->boolean('allow_subdomain');
        $data['allow_sslcommerz'] = $request->boolean('allow_sslcommerz');
        $data['allow_pixel_tracking'] = $request->boolean('allow_pixel_tracking');
        $data['remove_branding'] = $request->boolean('remove_branding');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
