<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPlacer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $orders = Order::when(in_array($status, array_keys(Order::FLOW), true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('seller.orders.index', ['orders' => $orders, 'status' => $status]);
    }

    public function show(Order $order)
    {
        return view('seller.orders.show', ['order' => $order->load('items')]);
    }

    public function status(Request $request, Order $order)
    {
        $to = $request->validate(['status' => ['required', 'string']])['status'];
        OrderPlacer::changeStatus($order->load('items'), $to);

        return back()->with('status', 'Order is now '.$to.'.');
    }

    public function payment(Request $request, Order $order)
    {
        $to = $request->validate(['payment_status' => ['required', 'in:paid,failed,unpaid']])['payment_status'];
        abort_if($order->status === 'cancelled', 422, 'Cancelled order.');
        $order->update(['payment_status' => $to]);

        return back()->with('status', 'Payment marked '.$to.'.');
    }
}
