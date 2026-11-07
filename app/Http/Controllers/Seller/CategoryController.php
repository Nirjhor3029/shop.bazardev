<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('seller.categories.index', ['categories' => Category::withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $base = Str::slug($data['name']) ?: 'category';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        Category::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('status', 'Category added.');
    }

    public function destroy(Category $category)
    {
        $category->delete(); // products keep existing, category_id becomes null

        return back()->with('status', 'Category deleted.');
    }
}
