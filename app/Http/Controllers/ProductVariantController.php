<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{

    public function store(Request $request, Product $product)
    {
        if ((int) $product->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName = uniqid('pv_') . '.' .
                $request->file('image')->extension();

            $request->file('image')->storeAs(
                'product_variants',
                $imageName,
                'public'
            );
        }

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => $data['name'],
            'status' => $data['status'],
            'price' => 0,
            'image' => $imageName,
        ]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Varian produk berhasil ditambahkan.');
    }

    public function edit(Product $product, ProductVariant $variant)
    {
        if ($product->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }

        if ($variant->product_id != $product->id) {
            abort(404);
        }

        return view('product_variants.edit', compact('product', 'variant'));
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        if ($product->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }

        if ($variant->product_id != $product->id) {
            abort(404);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],
        ]);

        $imageName = $variant->image;

        if ($request->hasFile('image')) {

            if ($variant->image) {
                Storage::disk('public')
                    ->delete('product_variants/' . $variant->image);
            }

            $imageName = uniqid('pv_') . '.' .
                $request->file('image')->extension();

            $request->file('image')->storeAs(
                'product_variants',
                $imageName,
                'public'
            );
        }

        $variant->update([
            'name' => $request->name,
            'status' => $request->status,
            'image' => $imageName,
        ]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Varian produk berhasil diperbarui.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        if ($product->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }

        if ($variant->product_id != $product->id) {
            abort(404);
        }

        if ($variant->image) {
            Storage::disk('public')->delete(
                'product_variants/' . $variant->image
            );
        }

        $variant->delete();

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Varian berhasil dihapus.');
    }
}