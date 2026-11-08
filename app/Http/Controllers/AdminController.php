<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stores = Store::with(['user', 'plan'])->latest()->paginate(30);
        $ids = $stores->pluck('id');
        $products = Product::withoutGlobalScopes()->whereIn('store_id', $ids)->selectRaw('store_id, count(*) c')->groupBy('store_id')->pluck('c', 'store_id');
        $orders = Order::withoutGlobalScopes()->whereIn('store_id', $ids)->selectRaw('store_id, count(*) c, coalesce(sum(case when status != "cancelled" then total end),0) t')->groupBy('store_id')->get()->keyBy('store_id');

        return view('admin.index', ['stores' => $stores, 'products' => $products, 'orders' => $orders, 'plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function status(Request $request, Store $store)
    {
        $to = $request->validate(['status' => ['required', 'in:published,suspended,draft']])['status'];
        $store->update(['status' => $to]);

        return back()->with('status', "{$store->slug} is now {$to}.");
    }

    /** Manual plan assignment - e.g. after the seller pays the admin directly outside the platform. */
    public function plan(Request $request, Store $store)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'plan_expires_at' => ['nullable', 'date'],
        ]);
        $store->update(['plan_id' => $data['plan_id'], 'plan_expires_at' => $data['plan_expires_at'] ?? null]);

        return back()->with('status', "{$store->slug}'s plan updated.");
    }
}
