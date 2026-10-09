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
        $productionOrders = ProductionOrder::with([
            'productVariant.product',
            'branch'
        ])
            ->whereHas('productVariant.product', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
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

        $branch = $this->findBranch($data['branch_id'] ?? null);

        $recipe = $variant->recipe;

        if (!$recipe) {
            return $this->recipeMissingResponse();
        }

        $data['quantity'] = $recipe->quantity * $data['batch_count'];

        $users = $this->findActiveEmployees(
            $data['employees'],
            $data['branch_id'] ?? null
        );

        $ingredients = $this->findRecipeIngredients(
            $recipe,
            $data['branch_id'] ?? null
        );

        $costs = $this->calculateCosts(
            $recipe,
            $data,
            $users,
            $ingredients
        );

        DB::transaction(function () use ($data, $variant, $branch, $recipe, $users, $ingredients, $costs) {
            $productionOrder = ProductionOrder::create([
                'product_variant_id' => $variant->id,
                'branch_id' => $branch?->id,
                'batch_count' => $data['batch_count'],
                'order_type' => 'regular',
                'quantity' => $data['quantity'],
                'production_date' => $data['production_date'],
                'status' => 'planned',
                'bop_cost' => $data['bop_cost'],
                'profit_percentage' => $data['profit_percentage'],
                'hpp_per_unit' => $costs['hppPerUnit'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncIngredients(
                $productionOrder,
                $ingredients,
                $costs['factor']
            );

            $this->syncEmployees(
                $productionOrder,
                $data['employees'],
                $users
            );
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
            fn($ingredient) =>
                $ingredient->pivot->quantity_used *
                $ingredient->pivot->unit_cost_used
        );

        $laborCost = $productionOrder->users->sum(
            fn($user) =>
                $user->pivot->labor_hours *
                $user->wage_rate_per_hour
        );

        $totalCost =
            $materialCost +
            $laborCost +
            $productionOrder->bop_cost;

        $hppPerUnit = $productionOrder->hpp_per_unit;

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

        if ($productionOrder->status === 'completed') {
            return redirect()
                ->route('production_orders.show', $productionOrder)
                ->with('error', 'Produksi yang sudah selesai tidak dapat diubah.');
        }

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

    public function update(
        Request $request,
        ProductionOrder $productionOrder
    ) {
        $this->authorizeTenant($productionOrder);

        if ($productionOrder->status === 'completed') {
            return redirect()
                ->route('production_orders.show', $productionOrder)
                ->with('error', 'Produksi yang sudah selesai tidak dapat diubah.');
        }

        $data = $this->validateRequest($request);

        $variant = $this->findVariant($data['product_variant_id']);

        $branch = $this->findBranch($data['branch_id'] ?? null);

        $recipe = $variant->recipe;

        if (!$recipe) {
            return $this->recipeMissingResponse();
        }

        // Hitung jumlah produksi dari batch
        $data['quantity'] = $recipe->quantity * $data['batch_count'];

        $users = $this->findActiveEmployees(
            $data['employees'],
            $data['branch_id'] ?? null
        );

        $ingredients = $this->findRecipeIngredients(
            $recipe,
            $data['branch_id'] ?? null
        );

        $costs = $this->calculateCosts(
            $recipe,
            $data,
            $users,
            $ingredients
        );

        DB::transaction(function () use ($productionOrder, $data, $variant, $branch, $recipe, $users, $ingredients, $costs) {
            $productionOrder->update([
                'product_variant_id' => $variant->id,
                'branch_id' => $branch?->id,
                'batch_count' => $data['batch_count'],
                'quantity' => $data['quantity'],
                'production_date' => $data['production_date'],
                'bop_cost' => $data['bop_cost'],
                'profit_percentage' => $data['profit_percentage'],
                'hpp_per_unit' => $costs['hppPerUnit'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Hapus data lama

            $productionOrder->ingredients()->detach();
            $productionOrder->users()->detach();

            $this->syncIngredients(
                $productionOrder,
                $ingredients,
                $costs['factor']
            );

            $this->syncEmployees(
                $productionOrder,
                $data['employees'],
                $users
            );
        });

        return redirect()
            ->route('production_orders.show', $productionOrder)
            ->with('success', 'Produksi berhasil diperbarui.');
    }

    public function updateStatus(
        Request $request,
        ProductionOrder $productionOrder
    ) {
        $this->authorizeTenant($productionOrder);

        $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        if (
            $request->status === 'in_progress' &&
            $productionOrder->status === 'planned'
        ) {
            $productionOrder->update([
                'status' => 'in_progress'
            ]);

            return back()->with(
                'success',
                'Status produksi diubah menjadi berlangsung.'
            );
        }

        if (
            $request->status === 'completed' &&
            $productionOrder->status === 'in_progress'
        ) {
            return $this->completeProduction($productionOrder);
        }

        return back()->with(
            'error',
            'Perubahan status produksi tidak dapat dilakukan.'
        );
    }

    private function completeProduction(
        ProductionOrder $productionOrder
    ) {
        $productionOrder->load([
            'ingredients',
            'productVariant',
        ]);

        try {
            DB::transaction(function () use ($productionOrder) {
                // Cek stok terlebih dahulu
                foreach ($productionOrder->ingredients as $ingredient) {
                    if (
                        $ingredient->stock <
                        $ingredient->pivot->quantity_used
                    ) {
                        throw new \RuntimeException(
                            'Stok ' .
                            $ingredient->name .
                            ' tidak mencukupi.'
                        );
                    }
                }

                // Kurangi stok
                foreach ($productionOrder->ingredients as $ingredient) {
                    $ingredient->decrement(
                        'stock',
                        $ingredient->pivot->quantity_used
                    );
                }

                // Ubah status
                $productionOrder->update([
                    'status' => 'completed'
                ]);

                // Update rata-rata HPP dan harga jual
                $this->updateAverageHppAndSellingPrice(
                    $productionOrder->productVariant,
                    $productionOrder->profit_percentage
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Produksi gagal diselesaikan.'
            );
        }

        return back()->with(
            'success',
            'Produksi selesai dan stok bahan baku berhasil diperbarui.'
        );
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        if ($productionOrder->status === 'completed') {
            return redirect()
                ->route('production_orders.index')
                ->with('error', 'Produksi yang sudah selesai tidak dapat dihapus.');
        }

        DB::transaction(function () use ($productionOrder) {
            $productionOrder->users()->detach();
            $productionOrder->ingredients()->detach();
            $productionOrder->delete();
        });

        return redirect()
            ->route('production_orders.index')
            ->with('success', 'Produksi berhasil dihapus.');
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------

    private function tenantId()
    {
        return auth()->user()->tenant_id;
    }

    private function authorizeTenant(
        ProductionOrder $productionOrder
    ): void {
        if ($productionOrder->branch_id) {
            if (
                !$productionOrder->branch ||
                $productionOrder->branch->tenant_id != $this->tenantId()
            ) {
                abort(403);
            }

            return;
        }
        
        if (
            !$productionOrder->productVariant ||
            !$productionOrder->productVariant->product ||
            $productionOrder->productVariant->product->tenant_id
            != $this->tenantId()
        ) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $variants = ProductVariant::with('product')
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
            ->whereHas('recipe')
            ->get();

        $branches = Branch::where(
            'tenant_id',
            $this->tenantId()
        )->get();

        $users = User::where(
            'tenant_id',
            $this->tenantId()
        )
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->where('name', '!=', 'Owner');
            })
            ->get();

        return compact(
            'variants',
            'branches',
            'users'
        );
    }

    private function validateRequest(Request $request): array
    {
        $request->merge([
            'bop_cost' => str_replace(
                '.',
                '',
                $request->bop_cost
            ),
        ]);

        $hasBranches = Branch::where(
            'tenant_id',
            $this->tenantId()
        )->exists();

        return $request->validate([
            'product_variant_id' => [
                'required',
                'exists:product_variants,id'
            ],

            'branch_id' => [
                $hasBranches ? 'required' : 'nullable',
                'nullable',
                'exists:branches,id'
            ],

            'batch_count' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'production_date' => [
                'required',
                'date'
            ],

            'bop_cost' => [
                'required',
                'numeric',
                'min:0'
            ],

            'profit_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100'
            ],

            'notes' => [
                'nullable',
                'string'
            ],

            'employees' => [
                'required',
                'array',
                'min:1'
            ],

            'employees.*.user_id' => [
                'required',
                'exists:users,id'
            ],

            'employees.*.job_description' => [
                'required',
                'string',
                'max:255'
            ],

            'employees.*.labor_hours' => [
                'required',
                'numeric',
                'gt:0'
            ],
        ]);
    }

    private function findVariant($id): ProductVariant
    {
        return ProductVariant::with(
            'recipe.ingredients'
        )
            ->where('id', $id)
            ->whereHas('product', function ($query) {
                $query->where(
                    'tenant_id',
                    $this->tenantId()
                );
            })
            ->firstOrFail();
    }

    private function findBranch($id)
    {
        if (!$id) {
            return null;
        }

        return Branch::where('id', $id)
            ->where(
                'tenant_id',
                $this->tenantId()
            )
            ->firstOrFail();
    }

    private function findActiveEmployees(
        array $employees,
        $branchId = null
    ) {
        $ids = collect($employees)
            ->pluck('user_id')
            ->unique();

        $query = User::whereIn('id', $ids)
            ->where(
                'tenant_id',
                $this->tenantId()
            )
            ->where('status', 'active');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereNull('branch_id');
        }

        $users = $query
            ->get()
            ->keyBy('id');

        if ($users->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'employees' =>
                    'Terdapat karyawan yang tidak sesuai dengan cabang produksi.'
            ]);
        }

        return $users;
    }

    private function calculateCosts(
        $recipe,
        array $data,
        $users,
        $ingredients
    ): array {
        $factor = $data['batch_count'];

        $materialCost = $ingredients->sum(
            fn($ingredient) =>
                $ingredient->pivot->quantity
                * $factor
                * $ingredient->unit_cost
        );

        $laborCost = collect($data['employees'])->sum(
            fn($employee) =>
                $employee['labor_hours']
                * $users[$employee['user_id']]->wage_rate_per_hour
        );

        $totalCost =
            $materialCost +
            $laborCost +
            $data['bop_cost'];

        $hppPerUnit = $data['quantity'] > 0
            ? $totalCost / $data['quantity']
            : 0;

        return [
            'factor' => $factor,
            'materialCost' => $materialCost,
            'laborCost' => $laborCost,
            'totalCost' => $totalCost,
            'hppPerUnit' => $hppPerUnit,
        ];
    }

    private function updateAverageHppAndSellingPrice(
        ProductVariant $variant,
        float $profitPercentage
    ): void {
        $averageHpp = ProductionOrder::where(
            'product_variant_id',
            $variant->id
        )
            ->where('status', 'completed')
            ->where('hpp_per_unit', '>', 0)
            ->avg('hpp_per_unit');

        if (!$averageHpp) {
            return;
        }

        $sellingPrice =
            $averageHpp +
            ($averageHpp * ($profitPercentage / 100));

        $variant->update([
            'price' => round($sellingPrice, 2),
        ]);
    }

    private function findRecipeIngredients($recipe, $branchId)
    {
        $ingredients = $recipe->ingredients;

        if ($branchId) {
            $ingredients = $ingredients->filter(
                fn($ingredient) => $ingredient->branch_id == $branchId
            );
        } else {
            $ingredients = $ingredients->filter(
                fn($ingredient) => $ingredient->branch_id === null
            );
        }

        if ($ingredients->count() !== $recipe->ingredients->count()) {
            throw ValidationException::withMessages([
                'branch_id' =>
                    'Terdapat bahan pada resep yang tidak tersedia pada cabang yang dipilih.'
            ]);
        }

        return $ingredients;
    }

    private function syncIngredients(
        ProductionOrder $productionOrder,
        $ingredients,
        float $factor
    ): void {
        foreach ($ingredients as $ingredient) {
            $productionOrder->ingredients()->attach(
                $ingredient->id,
                [
                    'quantity_used' =>
                        $ingredient->pivot->quantity * $factor,

                    'unit_cost_used' =>
                        $ingredient->unit_cost,
                ]
            );
        }
    }

    private function syncEmployees(
        ProductionOrder $productionOrder,
        array $employees,
        $users
    ): void {
        foreach ($employees as $employee) {
            $productionOrder->users()->attach(
                $users[$employee['user_id']]->id,
                [
                    'job_description' =>
                        $employee['job_description'],

                    'status' => 'assigned',

                    'labor_hours' =>
                        $employee['labor_hours'],
                ]
            );
        }
    }

    private function recipeMissingResponse()
    {
        return back()
            ->withInput()
            ->with(
                'error',
                'Produk - Varian ini belum memiliki resep.'
            );
    }
}