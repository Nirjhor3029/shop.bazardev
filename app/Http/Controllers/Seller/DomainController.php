<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\DomainCheck;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $domains = DomainCheck::where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('seller.domains.index', ['domains' => $domains]);
    }

    public function destroy(Request $request, DomainCheck $domainCheck)
    {
        abort_unless($domainCheck->user_id === $request->user()->id, 404);
        $domainCheck->delete();

        return back()->with('status', 'Removed.');
    }
}
