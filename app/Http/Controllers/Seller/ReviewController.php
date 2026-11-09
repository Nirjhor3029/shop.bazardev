<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : 'pending';
        $reviews = ProductReview::with('product')->where('status', $status)->latest()->paginate(20)->withQueryString();

        return view('seller.reviews.index', ['reviews' => $reviews, 'status' => $status]);
    }

    public function update(Request $request, ProductReview $review)
    {
        $to = $request->validate(['status' => ['required', 'in:approved,rejected']])['status'];
        $review->update(['status' => $to]);
        $review->product?->recomputeRating();

        return back()->with('status', 'Review '.$to.'.');
    }
}
