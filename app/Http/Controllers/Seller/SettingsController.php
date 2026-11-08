<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const MANUAL = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'];

    public function edit()
    {
        return view('seller.settings.edit', [
            'store' => app('currentStore'),
            'methods' => PaymentMethod::get()->keyBy('type'),
            'manual' => self::MANUAL,
        ]);
    }

    public function update(Request $request)
    {
        $store = app('currentStore');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'delivery_charge' => ['required', 'numeric', 'min:0', 'max:100000'],
            'free_delivery_over' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'cod' => ['nullable', 'boolean'],
            'pay' => ['nullable', 'array'],
            'pay.*.number' => ['nullable', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'pay.*.instructions' => ['nullable', 'string', 'max:300'],
            'sslcommerz_on' => ['nullable', 'boolean'],
            'sslcommerz_store_id' => ['nullable', 'string', 'max:60'],
            'sslcommerz_store_passwd' => ['nullable', 'string', 'max:200'],
            'sslcommerz_sandbox' => ['nullable', 'boolean'],
        ], ['pay.*.number.regex' => 'Enter a valid mobile number like 01712345678.']);

        $store->update([
            'name' => $data['name'],
            'settings' => array_merge($store->settings ?? [], [
                'delivery_charge' => (float) $data['delivery_charge'],
                'free_delivery_over' => (float) ($data['free_delivery_over'] ?? 0),
            ]),
        ]);

        PaymentMethod::updateOrCreate(['type' => 'cod'], ['is_active' => $request->boolean('cod')]);
        foreach (array_keys(self::MANUAL) as $type) {
            $row = $data['pay'][$type] ?? [];
            $number = $row['number'] ?? null;
            PaymentMethod::updateOrCreate(['type' => $type], [
                'number' => $number,
                'instructions' => $row['instructions'] ?? null,
                'is_active' => $request->boolean("pay_on.$type") && filled($number), // a method without a number cannot be on
            ]);
        }

        $storeId = $data['sslcommerz_store_id'] ?? null;
        $ssl = PaymentMethod::firstOrNew(['type' => 'sslcommerz']);
        $ssl->config = ['store_id' => $storeId, 'sandbox' => $request->boolean('sslcommerz_sandbox')];
        if (filled($data['sslcommerz_store_passwd'] ?? null)) {
            $ssl->secret = $data['sslcommerz_store_passwd']; // only overwrite the saved password when a new one was actually typed
        }
        $ssl->is_active = $request->boolean('sslcommerz_on') && filled($storeId) && filled($ssl->secret);
        $ssl->save();

        return back()->with('status', 'Settings saved.');
    }
}
