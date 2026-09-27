<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientRestock;
use Illuminate\Http\Request;

class IngredientRestockController extends Controller
{
    public function create()
    {
        $ingredients = Ingredient::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        return view('ingredient_restocks.create', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'restock_date' => ['required', 'date'],
        ]);

        $ingredient = Ingredient::where('id', $request->ingredient_id)
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->firstOrFail();

        // Hitung nilai stok lama
        $oldTotal = $ingredient->stock * $ingredient->unit_cost;

        // Hitung nilai restock
        $newTotal = $request->quantity * $request->unit_price;

        // Hitung stok baru
        $newStock = $ingredient->stock + $request->quantity;

        // Hitung harga rata-rata baru
        $newUnitCost = ($oldTotal + $newTotal) / $newStock;

        // Simpan riwayat restock
        IngredientRestock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_cost' => $newTotal,
            'restock_date' => $request->restock_date,
        ]);

        // Update stok dan harga rata-rata
        $ingredient->update([
            'stock' => $newStock,
            'unit_cost' => $newUnitCost,
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Restock berhasil dilakukan.');
    }
}