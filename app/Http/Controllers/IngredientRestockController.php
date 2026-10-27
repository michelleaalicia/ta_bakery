<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\IngredientRestock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IngredientRestockController extends Controller
{
    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        $branches = Branch::where('tenant_id', $tenantId)
            ->when(auth()->user()->role?->name !== 'Owner', fn ($q) => $q->whereKey(auth()->user()->branch_id))
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::where('tenant_id', $tenantId)
            ->whereNotNull('branch_id')
            ->when(auth()->user()->role?->name !== 'Owner', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->orderBy('name')
            ->get();

        return view('ingredient_restocks.create', compact(
            'branches',
            'ingredients'
        ));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role?->name !== 'Owner') {
            abort_if(!auth()->user()->branch_id, 403, 'Akun karyawan belum memiliki cabang.');
            $request->merge(['branch_id' => auth()->user()->branch_id]);
        }
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'restock_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->subDay()->toDateString(),
                'before_or_equal:' . now()->toDateString(),
            ],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $branch = Branch::where('tenant_id', $tenantId)
            ->findOrFail($data['branch_id']);

        $ingredient = Ingredient::where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->find($data['ingredient_id']);

        if (!$ingredient) {
            throw ValidationException::withMessages([
                'ingredient_id' => 'Bahan baku tidak tersedia di cabang yang dipilih.',
            ]);
        }

        DB::transaction(function () use ($data, $ingredient, $tenantId) {

            $ingredient = Ingredient::where('tenant_id', $tenantId)
                ->whereKey($ingredient->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldTotal = $ingredient->stock * $ingredient->unit_cost;

            $newTotal = $data['quantity'] * $data['unit_price'];

            $newStock = $ingredient->stock + $data['quantity'];

            $newUnitCost = ($oldTotal + $newTotal) / $newStock;

            IngredientRestock::create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_cost' => $newTotal,
                'restock_date' => $data['restock_date'],
            ]);

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
