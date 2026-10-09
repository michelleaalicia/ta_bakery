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
            ->where('tenant_id', auth()->user()->tenant_id)
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
        $tenantId = auth()->user()->tenant_id;

        $hasBranches = Branch::where('tenant_id', $tenantId)->exists();

        $request->validate([
            'branch_id' => [
                $hasBranches ? 'required' : 'nullable',
                'exists:branches,id',
            ],
            'name' => ['required', 'string', 'max:45'],
            'unit' => ['required', 'string', 'max:45'],
            'min_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = null;

        if ($request->filled('branch_id')) {
            $branch = Branch::where('id', $request->branch_id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            $branchId = $branch->id;
        }

        Ingredient::create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
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

        $tenantId = auth()->user()->tenant_id;

        $hasBranches = Branch::where('tenant_id', $tenantId)->exists();

        $request->validate([
            'branch_id' => [
                $hasBranches ? 'required' : 'nullable',
                'exists:branches,id',
            ],
            'name' => ['required', 'string', 'max:45'],
            'unit' => ['required', 'string', 'max:45'],
            'min_stock' => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = null;

        if ($request->filled('branch_id')) {
            $branch = Branch::where('id', $request->branch_id)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            $branchId = $branch->id;
        }

        $ingredient->update([
            'branch_id' => $branchId,
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
        $this->checkTenant($ingredient);

        if ($ingredient->recipes()->exists()) {
            return redirect()
                ->route('ingredients.index')
                ->with(
                    'error',
                    'Bahan baku tidak dapat dihapus karena masih digunakan dalam resep.'
                );
        }

        if ($ingredient->productionOrders()->exists()) {
            return redirect()
                ->route('ingredients.index')
                ->with(
                    'error',
                    'Bahan baku tidak dapat dihapus karena sudah digunakan dalam produksi.'
                );
        }

        $ingredient->restocks()->delete();
        $ingredient->delete();

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }

    private function checkTenant(Ingredient $ingredient)
    {
        if ($ingredient->tenant_id != auth()->user()->tenant_id) {
            abort(403);
        }
    }
}