<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\IngredientMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::with('branch')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when(auth()->user()->role?->name !== 'Owner', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->get();

        return view('ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        $branches = Branch::where(
            'tenant_id',
            auth()->user()->tenant_id
        )->when(auth()->user()->role?->name !== 'Owner', fn ($q) => $q->whereKey(auth()->user()->branch_id))->get();

        return view('ingredients.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $this->forceEmployeeBranch($request);

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

        $this->ensureNoDuplicate($tenantId, $branchId, $request->name, $request->unit);
        DB::transaction(function () use ($tenantId, $branchId, $request) {
            $master = IngredientMaster::firstOrCreate([
                'tenant_id' => $tenantId,
                'name' => trim($request->name),
                'unit' => $request->unit,
            ]);
            Ingredient::create([
            'ingredient_master_id' => $master->id,
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'name' => trim($request->name),
            'unit' => $request->unit,
            'unit_cost' => 0,
            'stock' => 0,
            'min_stock' => $request->min_stock,
            ]);
        });

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
        )->when(auth()->user()->role?->name !== 'Owner', fn ($q) => $q->whereKey(auth()->user()->branch_id))->get();

        return view('ingredients.edit', compact('ingredient', 'branches'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $this->checkTenant($ingredient);

        $tenantId = auth()->user()->tenant_id;
        $this->forceEmployeeBranch($request);

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

        $masterUsed = Ingredient::where('ingredient_master_id', $ingredient->ingredient_master_id)
            ->whereHas('recipes')->exists();
        if ($masterUsed && ($ingredient->name !== trim($request->name)
            || $ingredient->unit !== $request->unit)) {
            throw ValidationException::withMessages([
                'name' => 'Nama dan satuan bahan standar yang sudah digunakan resep tidak boleh diubah.',
            ]);
        }
        if ((string)$ingredient->branch_id !== (string)$branchId
            && ($ingredient->stock > 0 || $ingredient->restocks()->exists()
                || $ingredient->productionOrders()->exists())) {
            throw ValidationException::withMessages([
                'branch_id' => 'Cabang bahan yang sudah memiliki stok atau riwayat tidak boleh dipindah.',
            ]);
        }
        $this->ensureNoDuplicate($tenantId, $branchId, $request->name, $request->unit, $ingredient->id);
        $master = IngredientMaster::firstOrCreate([
            'tenant_id' => $tenantId,
            'name' => trim($request->name),
            'unit' => $request->unit,
        ]);
        $ingredient->update([
            'ingredient_master_id' => $master->id,
            'branch_id' => $branchId,
            'name' => trim($request->name),
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

        // Bahan yang menjadi rujukan resep tidak boleh dihapus.
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

        if ($ingredient->stock > 0 || $ingredient->restocks()->exists()) {
            return back()->with('error', 'Bahan yang memiliki stok atau riwayat restock tidak dapat dihapus.');
        }
        $ingredient->delete();

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dihapus.');
    }

    private function forceEmployeeBranch(Request $request): void
    {
        if (auth()->user()->role?->name !== 'Owner') {
            abort_if(!auth()->user()->branch_id, 403, 'Akun karyawan belum memiliki cabang.');
            $request->merge(['branch_id' => auth()->user()->branch_id]);
        }
    }

    private function ensureNoDuplicate($tenantId, $branchId, $name, $unit, $exceptId = null): void
    {
        $query = Ingredient::where('tenant_id', $tenantId)
            ->where('name', trim($name))->where('unit', $unit);
        $branchId === null ? $query->whereNull('branch_id') : $query->where('branch_id', $branchId);
        if ($exceptId !== null) $query->where('id', '!=', $exceptId);
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Bahan dengan nama dan satuan ini sudah ada di cabang tersebut.',
            ]);
        }
    }

    private function checkTenant(Ingredient $ingredient)
    {
        if ($ingredient->tenant_id != auth()->user()->tenant_id ||
            (auth()->user()->role?->name !== 'Owner' && (int) $ingredient->branch_id !== (int) auth()->user()->branch_id)) {
            abort(403);
        }
    }
}