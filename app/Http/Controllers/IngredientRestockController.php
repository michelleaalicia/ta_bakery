<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientRestock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientRestockController extends Controller
{
    public function create()
    {
        $ingredients = Ingredient::with('branch')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();

        return view('ingredient_restocks.create', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ingredient_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'restock_date' => ['required', 'date'],
        ]);

        $ingredient = Ingredient::where('id', $request->ingredient_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $ingredient) {
            // Hitung nilai stok lama
            $oldTotal = $ingredient->stock * $ingredient->unit_cost;

            // Hitung nilai pembelian baru
            $newTotal = $request->quantity * $request->unit_price;

            // Hitung stok setelah restock
            $newStock = $ingredient->stock + $request->quantity;

            // Hitung harga rata-rata per satuan
            $newUnitCost = ($oldTotal + $newTotal) / $newStock;

            // Simpan riwayat restock
            IngredientRestock::create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_cost' => $newTotal,
                'restock_date' => $request->restock_date,
            ]);

            // Perbarui stok dan harga rata-rata
            $ingredient->update([
                'stock' => $newStock,
                'unit_cost' => $newUnitCost,
            ]);
        });

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Restock berhasil dilakukan.');
    }
}
