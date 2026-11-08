<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        return view('seller.products.index', ['products' => Product::with('images', 'category')->latest()->paginate(20)]);
    }

    public function create()
    {
        return view('seller.products.form', ['product' => new Product(['is_active' => true, 'stock' => 0]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request, ImageStore $images)
    {
        $data = $this->validated($request);
        $product = Product::create($data + ['slug' => $this->uniqueSlug($data['name'])]);
        $this->addImages($request, $product, $images);

        return redirect()->route('seller.products.edit', $product)->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('seller.products.form', ['product' => $product->load('images'), 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product, ImageStore $images)
    {
        $product->update($this->validated($request));
        foreach ($request->input('remove_images', []) as $id) {
            if ($img = ProductImage::where('product_id', $product->id)->find($id)) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }
        }
        $this->addImages($request, $product, $images);
        $product->update(['is_sample' => false]);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $product->delete();

        return redirect()->route('seller.products.index')->with('status', 'Product deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'category_id' => ['nullable', 'integer'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        // category must belong to this store (scope makes foreign ids invisible)
        if (! empty($data['category_id']) && ! Category::whereKey($data['category_id'])->exists()) {
            $data['category_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        unset($data['images']);

        return $data;
    }

    private function addImages(Request $request, Product $product, ImageStore $images): void
    {
        $order = (int) $product->images()->max('sort_order');
        foreach ($request->file('images', []) as $file) {
            $product->images()->create(['path' => $images->save($file, $product->store_id), 'sort_order' => ++$order]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
