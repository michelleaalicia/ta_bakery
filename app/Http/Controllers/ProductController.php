<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.status' => ['required', 'in:active,inactive'],
            'variants.*.image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],
        ]);

        $category = Category::where('id', $request->category_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $product = Product::create([
            'tenant_id' => auth()->user()->tenant_id,
            'category_id' => $category->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        foreach ($request->variants as $variant) {

            $imageName = null;

            if (isset($variant['image'])) {
                $imageName = uniqid('pv_') . '.' . $variant['image']->extension();

                $variant['image']->storeAs(
                    'product_variants',
                    $imageName,
                    'public'
                );
            }

            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variant['name'],
                'status' => $variant['status'],
                'image' => $imageName,
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $this->checkTenant($product);

        $product->load('category', 'variants');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->checkTenant($product);

        $categories = Category::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->checkTenant($product);

        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $category = Category::where('id', $request->category_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $product->update([
            'category_id' => $category->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $this->checkTenant($product);

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    private function checkTenant(Product $product)
    {
        if ($product->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }
}