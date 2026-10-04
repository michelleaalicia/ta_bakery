<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductionOrder;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionOrderController extends Controller
{
    public function index()
    {
        $productionOrders = ProductionOrder::with(['productVariant.product', 'branch'])
            ->whereHas('branch', fn($q) => $q->where('tenant_id', $this->tenantId()))
            ->latest()
            ->get();

        return view('production_orders.index', compact('productionOrders'));
    }

    public function create()
    {
        return view('production_orders.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        $variant = $this->findVariant($data['product_variant_id']);
        $branch = $this->findBranch($data['branch_id']);
        $recipe = $variant->recipe;

        if (!$recipe) {
            return $this->recipeMissingResponse();
        }

        $users = $this->findActiveEmployees($data['employees']);
        $costs = $this->calculateCosts($recipe, $data, $users);

        DB::transaction(function () use ($data, $variant, $branch, $recipe, $users, $costs) {
            $productionOrder = ProductionOrder::create([
                'product_variant_id' => $variant->id,
                'branch_id' => $branch->id,
                'order_type' => 'regular',
                'quantity' => $data['quantity'],
                'production_date' => $data['production_date'],
                'status' => 'planned',
                'bop_cost' => $data['bop_cost'],
                'profit_percentage' => $data['profit_percentage'],
                'selling_price' => $costs['sellingPrice'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncIngredients($productionOrder, $recipe, $costs['factor']);
            $this->syncEmployees($productionOrder, $data['employees'], $users);
        });

        return redirect()
            ->route('production_orders.index')
            ->with('success', 'Produksi berhasil ditambahkan.');
    }

    public function show(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        $productionOrder->load([
            'productVariant.product',
            'productVariant.recipe',
            'branch',
            'ingredients',
            'users',
        ]);

        $materialCost = $productionOrder->ingredients->sum(
            fn($ingredient) => $ingredient->pivot->quantity_used * $ingredient->pivot->unit_cost_used
        );

        $laborCost = $productionOrder->users->sum(
            fn($user) => $user->pivot->labor_hours * $user->wage_rate_per_hour
        );

        $totalCost = $materialCost + $laborCost + $productionOrder->bop_cost;

        $hppPerUnit = $productionOrder->quantity > 0
            ? $totalCost / $productionOrder->quantity
            : 0;

        return view('production_orders.show', compact(
            'productionOrder',
            'materialCost',
            'laborCost',
            'totalCost',
            'hppPerUnit'
        ));
    }

    public function edit(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        $productionOrder->load([
            'productVariant.product',
            'productVariant.recipe',
            'users',
        ]);

        return view('production_orders.edit', array_merge(
            ['productionOrder' => $productionOrder],
            $this->formData()
        ));
    }

    public function update(Request $request, ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        $data = $this->validateRequest($request);

        $variant = $this->findVariant($data['product_variant_id']);
        $branch = $this->findBranch($data['branch_id']);
        $recipe = $variant->recipe;

        if (!$recipe) {
            return $this->recipeMissingResponse();
        }

        $users = $this->findActiveEmployees($data['employees']);
        $costs = $this->calculateCosts($recipe, $data, $users);

        DB::transaction(function () use ($productionOrder, $data, $variant, $branch, $recipe, $users, $costs) {
            $productionOrder->update([
                'product_variant_id' => $variant->id,
                'branch_id' => $branch->id,
                'quantity' => $data['quantity'],
                'production_date' => $data['production_date'],
                'bop_cost' => $data['bop_cost'],
                'profit_percentage' => $data['profit_percentage'],
                'selling_price' => $costs['sellingPrice'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Hapus data lama, lalu simpan ulang berdasarkan input terbaru
            $productionOrder->ingredients()->detach();
            $productionOrder->users()->detach();

            $this->syncIngredients($productionOrder, $recipe, $costs['factor']);
            $this->syncEmployees($productionOrder, $data['employees'], $users);
        });

        return redirect()
            ->route('production_orders.show', $productionOrder)
            ->with('success', 'Produksi berhasil diperbarui.');
    }

    public function updateStatus(Request $request, ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        // Direncanakan -> Berlangsung
        if ($request->status === 'in_progress' && $productionOrder->status === 'planned') {
            $productionOrder->update(['status' => 'in_progress']);

            return back()->with('success', 'Status produksi diubah menjadi berlangsung.');
        }

        // Berlangsung -> Selesai
        if ($request->status === 'completed' && $productionOrder->status === 'in_progress') {
            return $this->completeProduction($productionOrder);
        }

        return back()->with('error', 'Perubahan status produksi tidak dapat dilakukan.');
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        DB::transaction(function () use ($productionOrder) {
            $productionOrder->users()->detach();
            $productionOrder->ingredients()->detach();
            $productionOrder->delete();
        });

        return redirect()
            ->route('production_orders.index')
            ->with('success', 'Produksi berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function tenantId()
    {
        return auth()->user()->tenant_id;
    }

    private function authorizeTenant(ProductionOrder $productionOrder): void
    {
        if ($productionOrder->branch->tenant_id != $this->tenantId()) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $variants = ProductVariant::with('product')
            ->whereHas('product', fn($q) => $q->where('tenant_id', $this->tenantId()))
            ->whereHas('recipe')
            ->get();

        $branches = Branch::where('tenant_id', $this->tenantId())->get();

        $users = User::where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->whereHas('role', fn($q) => $q->where('name', '!=', 'Owner'))
            ->get();

        return compact('variants', 'branches', 'users');
    }

    private function validateRequest(Request $request): array
    {
        $request->merge([
            'bop_cost' => str_replace('.', '', $request->bop_cost),
        ]);

        return $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'production_date' => ['required', 'date'],
            'bop_cost' => ['required', 'numeric', 'min:0'],
            'profit_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],

            'employees' => ['required', 'array', 'min:1'],
            'employees.*.user_id' => ['required', 'exists:users,id'],
            'employees.*.job_description' => ['required', 'string', 'max:255'],
            'employees.*.labor_hours' => ['required', 'numeric', 'gt:0'],
        ]);
    }

    private function findVariant($id): ProductVariant
    {
        return ProductVariant::with('recipe.ingredients')
            ->where('id', $id)
            ->whereHas('product', fn($q) => $q->where('tenant_id', $this->tenantId()))
            ->firstOrFail();
    }

    private function findBranch($id): Branch
    {
        return Branch::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    /**
     * Ambil semua karyawan sekaligus (1 query), keyed by id.
     */
    private function findActiveEmployees(array $employees)
    {
        $ids = collect($employees)->pluck('user_id')->unique();

        $users = User::whereIn('id', $ids)
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if ($users->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'employees' => 'Terdapat karyawan yang tidak valid atau tidak aktif.',
            ]);
        }

        return $users;
    }

    /**
     * Hitung biaya bahan, tenaga kerja, HPP per unit, dan harga jual.
     */
    private function calculateCosts($recipe, array $data, $users): array
    {
        // Perbandingan jumlah produksi dengan hasil resep
        $factor = $data['quantity'] / $recipe->quantity;

        $materialCost = $recipe->ingredients->sum(
            fn($ingredient) => $ingredient->pivot->quantity * $factor * $ingredient->unit_cost
        );

        $laborCost = collect($data['employees'])->sum(
            fn($employee) => $employee['labor_hours'] * $users[$employee['user_id']]->wage_rate_per_hour
        );

        $totalCost = $materialCost + $laborCost + $data['bop_cost'];
        $hppPerUnit = $totalCost / $data['quantity'];
        $sellingPrice = $hppPerUnit + ($hppPerUnit * ($data['profit_percentage'] / 100));

        return compact('factor', 'materialCost', 'laborCost', 'totalCost', 'hppPerUnit', 'sellingPrice');
    }

    // Simpan bahan yang digunakan untuk produksi
    private function syncIngredients(ProductionOrder $productionOrder, $recipe, float $factor): void
    {
        foreach ($recipe->ingredients as $ingredient) {
            $productionOrder->ingredients()->attach($ingredient->id, [
                'quantity_used' => $ingredient->pivot->quantity * $factor,
                'unit_cost_used' => $ingredient->unit_cost,
            ]);
        }
    }

    // Simpan karyawan produksi
    private function syncEmployees(ProductionOrder $productionOrder, array $employees, $users): void
    {
        foreach ($employees as $employee) {
            $productionOrder->users()->attach($users[$employee['user_id']]->id, [
                'job_description' => $employee['job_description'],
                'status' => 'assigned',
                'labor_hours' => $employee['labor_hours'],
            ]);
        }
    }

    private function recipeMissingResponse()
    {
        return back()
            ->withInput()
            ->with('error', 'Produk - Varian ini belum memiliki resep.');
    }

    private function completeProduction(ProductionOrder $productionOrder)
    {
        $productionOrder->load('ingredients');

        try {
            DB::transaction(function () use ($productionOrder) {
                // Cek stok terlebih dahulu
                foreach ($productionOrder->ingredients as $ingredient) {
                    if ($ingredient->stock < $ingredient->pivot->quantity_used) {
                        throw new \RuntimeException('Stok ' . $ingredient->name . ' tidak mencukupi.');
                    }
                }

                // Kurangi stok
                foreach ($productionOrder->ingredients as $ingredient) {
                    $ingredient->decrement('stock', $ingredient->pivot->quantity_used);
                }

                $productionOrder->update(['status' => 'completed']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Produksi gagal diselesaikan.');
        }

        return back()->with('success', 'Produksi selesai dan stok bahan baku berhasil diperbarui.');
    }
}