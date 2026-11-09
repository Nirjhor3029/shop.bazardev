<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status') === 'answered' ? 'answered' : 'pending';
        $questions = ProductQuestion::with('product')
            ->when($status === 'pending', fn ($q) => $q->whereNull('answer')->where('is_hidden', false))
            ->when($status === 'answered', fn ($q) => $q->whereNotNull('answer'))
            ->latest()->paginate(20)->withQueryString();

        return view('seller.questions.index', ['questions' => $questions, 'status' => $status]);
    }

    public function update(Request $request, ProductQuestion $question)
    {
        if ($request->boolean('hide')) {
            $question->update(['is_hidden' => true]);

            return back()->with('status', 'Question hidden.');
        }

        $data = $request->validate(['answer' => ['required', 'string', 'max:1000']]);
        $question->update(['answer' => $data['answer']]);

        return back()->with('status', 'Answer posted.');
    }
}
