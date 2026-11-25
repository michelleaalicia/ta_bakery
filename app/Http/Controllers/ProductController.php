<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\ProductionOrder;
use App\Models\Branch;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->withCount('variants')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();

        // HPP acuan: produksi reguler selesai terakhir pada setiap varian.
        $products->load('variants');
        $variantIds = $products->flatMap(fn($product) => $product->variants->pluck('id'));
        $lastProductions = ProductionOrder::whereIn('product_variant_id', $variantIds)
            ->where('order_type', 'regular')
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->get(['id', 'product_variant_id', 'hpp_per_unit'])
            ->unique('product_variant_id')
            ->keyBy('product_variant_id');

        $priceProducts = $products->map(function ($product) use ($lastProductions) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'variants' => $product->variants->map(function ($variant) use ($lastProductions) {
                    $last = $lastProductions->get($variant->id);
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'hpp' => $last ? (float) $last->hpp_per_unit : 0,
                        'has_completed' => (bool) $last,
                        'price' => (float) $variant->price,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('products.index', compact('products', 'priceProducts'));
    }

    public function updateSellingPrice(Request $request)
    {
        if (auth()->user()->role?->name !== 'Owner') {
            abort(403);
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'product_variant_id' => ['required', 'integer'],
            'final_price' => ['required', 'integer', 'min:1', 'max:9999999999999'],
        ], [
            'final_price.required' => 'Harga pilihan Owner wajib diisi.',
            'final_price.integer' => 'Harga jual harus berupa rupiah bulat.',
            'final_price.min' => 'Harga jual harus lebih dari Rp0.',
        ]);

        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($data['product_id']);

        $variant = ProductVariant::where('product_id', $product->id)
            ->findOrFail($data['product_variant_id']);

        // Periksa kembali di server, bukan hanya berdasarkan dropdown browser.
        $last = ProductionOrder::where('product_variant_id', $variant->id)
            ->where('order_type', 'regular')
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->first();

        if (!$last || (float) $last->hpp_per_unit <= 0) {
            return back()->with('error', 'Varian belum pernah selesai diproduksi atau HPP belum tersedia. Harga jual tidak diubah.');
        }

        $variant->update(['price' => $data['final_price']]);

        return redirect()->route('products.index')
            ->with('success', 'Harga jual varian berhasil diperbarui dan berlaku di seluruh cabang serta POS.');
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

        $tenantId = auth()->user()->tenant_id;

        $product->load([
            'category',
            'variants.stocks.branch',
        ]);

        $branches = Branch::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('products.show', compact('product', 'branches'));
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

        if ($product->variants()->exists()) {
            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'Produk tidak dapat dihapus karena masih memiliki varian. '
                    . 'Jika produk sudah tidak dijual, ubah statusnya menjadi Nonaktif.'
                );
        }

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