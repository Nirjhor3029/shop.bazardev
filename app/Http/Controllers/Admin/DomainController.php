<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainCheck;

class DomainController extends Controller
{
    public function index()
    {
        return view('admin.domains.index', ['domains' => DomainCheck::with('user')->latest()->paginate(40)]);
    }
}
