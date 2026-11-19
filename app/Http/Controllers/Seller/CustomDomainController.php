<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\StoreDomain;
use App\Services\DomainVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Pro-only: a seller's own domain (e.g. mydress.com) pointed at their store. This controller
 * only manages the StoreDomain record + its TXT-based ownership verification - actual request
 * routing for a verified domain (Caddy + ResolveStoreFromHost) is a separate, later change.
 */
class CustomDomainController extends Controller
{
    public function index()
    {
        $store = app('currentStore');

        return view('seller.custom-domain.index', [
            'store' => $store,
            'plan' => $store->activePlan(),
            'domain' => $store->domains()->latest()->first(),
        ]);
    }

    public function store(Request $request, DomainVerifier $verifier)
    {
        $store = app('currentStore');
        abort_unless($store->activePlan()->allow_custom_domain, 403, 'Custom domain needs the Pro plan.');

        if ($store->domains()->exists()) {
            return back()->withErrors(['domain' => 'Remove your current domain before adding a new one.']);
        }

        $data = $request->validate([
            'domain' => [
                'required', 'string', 'max:255',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?:\.[a-z0-9-]{1,63})+$/i',
                'not_in:'.config('shop.base_domain'),
                'unique:store_domains,domain',
            ],
        ], [
            'domain.regex' => 'Enter a plain domain like yourshop.com (no https:// or path).',
        ]);

        $domain = strtolower(trim($data['domain']));
        if ($domain === config('shop.base_domain') || str_ends_with($domain, '.'.config('shop.base_domain'))) {
            throw ValidationException::withMessages(['domain' => 'That domain is reserved for the platform itself.']);
        }

        $store->domains()->create([
            'domain' => $domain,
            'verification_token' => $verifier->newToken(),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Domain added. Follow the DNS instructions, then press Verify.');
    }

    public function recheck(Request $request, StoreDomain $storeDomain, DomainVerifier $verifier)
    {
        abort_unless($storeDomain->store_id === app('currentStore')->id, 404);

        $verified = $verifier->check($storeDomain);

        return back()->with('status', $verified
            ? 'Domain verified.'
            : 'Not verified yet - DNS changes can take a few hours to propagate. Try again later.');
    }

    public function destroy(Request $request, StoreDomain $storeDomain)
    {
        abort_unless($storeDomain->store_id === app('currentStore')->id, 404);
        $storeDomain->delete();

        return back()->with('status', 'Domain removed.');
    }
}
