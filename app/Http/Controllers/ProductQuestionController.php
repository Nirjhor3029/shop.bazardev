<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductQuestionController extends Controller
{
    /** Public, no-login "ask a question" form on the product page itself - no purchase needed, unlike reviews. */
    public function store(Request $request, string $slug)
    {
        $product = Product::where('is_active', true)->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:500'],
        ]);

        $product->questions()->create(['customer_name' => $data['name'], 'question' => $data['question']]);

        return back()->with('status', __('store.question_sent'));
    }
}
