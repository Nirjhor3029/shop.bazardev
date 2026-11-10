<?php

namespace App\Http\Controllers;

use App\Models\DomainCheck;
use App\Services\DomainAvailability;
use Illuminate\Http\Request;

class DomainCheckController extends Controller
{
    /** Public domain-availability checker. No login needed to use it. */
    public function show()
    {
        return view('market.domains');
    }

    /**
     * Shared by the public page and the seller dashboard's own domain tool - the only
     * difference is who's logged in, which decides who an available hit gets saved under.
     */
    public function check(Request $request, DomainAvailability $service)
    {
        $data = $request->validate(['domain' => ['required', 'string', 'max:255']]);

        $primary = $service->check($data['domain']);
        $alternatives = collect($service->checkAcrossCommonTlds($data['domain']))
            ->reject(fn ($r) => $r['domain'] === $primary['domain']) // already shown as the primary result
            ->values();

        $this->saveIfAvailable($request, $primary);
        $alternatives->each(fn ($r) => $this->saveIfAvailable($request, $r));

        return back()->with('domainResult', $primary)->with('domainAlternatives', $alternatives->all())->withInput();
    }

    private function saveIfAvailable(Request $request, array $result): void
    {
        if ($result['available'] === true) {
            DomainCheck::create([
                'user_id' => $request->user()?->id,
                'domain' => $result['domain'],
                'available' => true,
                'ip' => $request->ip(),
            ]);
        }
    }
}
