<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Branch;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->get();

        return view('ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        $branches = Branch::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('ingredients.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:45'],
            'unit' => ['required', 'string', 'max:45'],
            'min_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        Ingredient::create([
            'branch_id' => $branch->id,
            'name' => $request->name,
            'unit' => $request->unit,
            'unit_cost' => 0,
            'stock' => 0,
            'min_stock' => $request->min_stock,
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function edit(Ingredient $ingredient)
    {
        $this->checkTenant($ingredient);

        $branches = Branch::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->get();

        return view('ingredients.edit', compact('ingredient', 'branches'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $this->checkTenant($ingredient);

        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:45'],
            'unit' => ['required', 'string', 'max:45'],
            'min_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $ingredient->update([
            'branch_id' => $branch->id,
            'name' => $request->name,
            'unit' => $request->unit,
            'min_stock' => $request->min_stock,
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        if ($ingredient->branch->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }

        if ($ingredient->recipes()->exists()) {
            return redirect()
                ->route('ingredients.index')
                ->with('error', 'Bahan baku tidak dapat dihapus karena masih digunakan dalam resep.');
        }

        if ($ingredient->productionOrders()->exists()) {
            return redirect()
                ->route('ingredients.index')
                ->with('error', 'Bahan baku tidak dapat dihapus karena sudah digunakan dalam produksi.');
        }

        $ingredient->restocks()->delete();
        $ingredient->delete();

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }
    private function checkTenant(Ingredient $ingredient)
    {
        if ($ingredient->branch->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }

}