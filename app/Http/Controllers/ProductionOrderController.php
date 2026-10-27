<?php

namespace App\Http\Controllers;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Branch;
use App\Models\JobDescription;
use App\Models\ProductionOrder;
use App\Models\ProductVariant;
use App\Models\ProductVariantStock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductionOrderController extends Controller
{

    public function index()
    {
        $user = auth()->user();

        $productionOrders = ProductionOrder::with([
            'productVariant.product',
            'branch',
            'customOrder',
        ])
            ->where(function ($query) use ($user) {
                $query->where(function ($regular) use ($user) {
                    $regular->where('order_type', 'regular')
                        ->whereHas('productVariant.product', function ($product) use ($user) {
                            $product->where('tenant_id', $user->tenant_id);
                        });
                })
                    ->orWhere(function ($custom) use ($user) {
                        $custom->where('order_type', 'custom')
                            ->whereHas('customOrder', function ($order) use ($user) {
                                $order->where('tenant_id', $user->tenant_id);
                            });
                    });
            })
            ->when($user->role?->name !== 'Owner', function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
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

        DB::transaction(function () use ($data, $variant, $branch, $users, $ingredients, $costs) {
            $productionOrder = ProductionOrder::create([
                'product_variant_id' => $variant->id,
                'branch_id' => $branch?->id,
                'batch_count' => $data['batch_count'],
                'order_type' => 'regular',
                'quantity' => $data['quantity'],
                'production_date' => $data['production_date'],
                'status' => 'planned',
                'bop_cost' => $data['bop_cost'],
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
                ($user->pivot->labor_rate_per_hour ?? $user->wage_rate_per_hour)
        );

        $totalCost =
            $materialCost +
            $laborCost +
            $productionOrder->bop_cost;

        $hppPerUnit = (float) $productionOrder->quantity > 0
            ? $totalCost / (float) $productionOrder->quantity
            : 0;

        // Data pilihan tambahan hanya untuk produksi Custom yang berjalan.
        $availableCustomIngredients = collect();
        $availableCustomEmployees = collect();
        $availableCustomJobs = collect();
        $customActualsSaved = false;
        $customMaterialOptions = [];
        $customEmployeeOptions = [];
        $customJobOptions = [];

        if ($productionOrder->order_type === 'custom' && $productionOrder->status === 'in_progress') {
            $custom = $productionOrder->customOrder;
            if ($custom) {
                $custom->load(['materials', 'labors']);
                $availableCustomIngredients = Ingredient::where('tenant_id', $custom->tenant_id)
                    ->where('branch_id', $productionOrder->branch_id)
                    ->whereNotNull('ingredient_master_id')
                    ->whereNotIn('id', $productionOrder->ingredients->pluck('id'))
                    ->orderBy('name')->get(['id', 'name', 'unit', 'stock']);

                $availableCustomEmployees = User::where('tenant_id', $custom->tenant_id)
                    ->where('branch_id', $productionOrder->branch_id)
                    ->where('status', 'active')
                    ->whereHas('role', fn($q) => $q->where('name', 'Produksi'))
                    ->whereNotIn('id', $productionOrder->users->pluck('id'))
                    ->orderBy('name')->get(['id', 'name', 'wage_rate_per_hour']);

                $availableCustomJobs = JobDescription::where('tenant_id', $custom->tenant_id)
                    ->orderBy('name')->pluck('name');

                $customMaterialOptions = $availableCustomIngredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit])->values()->all();
                $customEmployeeOptions = $availableCustomEmployees->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values()->all();
                $customJobOptions = $availableCustomJobs->values()->all();

                $customActualsSaved = DB::table('users_has_production_orders')
                    ->where('production_order_id', $productionOrder->id)
                    ->whereNotNull('updated_at')->exists();
            }
        }

        return view('production_orders.show', compact(
            'productionOrder',
            'materialCost',
            'laborCost',
            'totalCost',
            'hppPerUnit',
            'availableCustomIngredients',
            'availableCustomEmployees',
            'availableCustomJobs',
            'customActualsSaved',
            'customMaterialOptions',
            'customEmployeeOptions',
            'customJobOptions'
        ));
    }

    public function edit(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        if ($productionOrder->status !== 'planned') {
            return redirect()
                ->route('production_orders.show', $productionOrder)
                ->with('error', 'Hanya produksi berstatus direncanakan yang dapat diubah.');
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

        if ($productionOrder->status !== 'planned') {
            return redirect()
                ->route('production_orders.show', $productionOrder)
                ->with('error', 'Hanya produksi berstatus direncanakan yang dapat diubah.');
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

    private function completeProduction(ProductionOrder $productionOrder)
    {
        if ($productionOrder->order_type === 'custom') {
            return $this->completeCustomProduction($productionOrder);
        }

        try {
            DB::transaction(function () use ($productionOrder) {
                // Kunci data produksi agar tidak diproses selesai dua kali bersamaan.
                $order = ProductionOrder::whereKey($productionOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status !== 'in_progress') {
                    throw new \RuntimeException(
                        'Produksi ini sudah diproses atau statusnya tidak sesuai.'
                    );
                }

                $order->load(['ingredients', 'users', 'productVariant']);

                // Periksa semua stok bahan sebelum mengurangi stok.
                foreach ($order->ingredients as $ingredient) {
                    $lockedIngredient = $ingredient->newQuery()
                        ->whereKey($ingredient->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedIngredient->stock < $ingredient->pivot->quantity_used) {
                        throw new \RuntimeException(
                            'Stok ' . $ingredient->name . ' tidak mencukupi.'
                        );
                    }
                }

                // Kurangi stok bahan baku.
                foreach ($order->ingredients as $ingredient) {
                    $lockedIngredient = $ingredient->newQuery()
                        ->whereKey($ingredient->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $lockedIngredient->decrement(
                        'stock',
                        $ingredient->pivot->quantity_used
                    );
                }

                // Kunci varian agar dua produksi untuk varian yang sama tidak membuat stok ganda.
                $variant = ProductVariant::whereKey($order->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Tambahkan hasil produksi ke stok produk jadi sesuai cabang.
                $stockQuery = ProductVariantStock::where(
                    'product_variant_id',
                    $order->product_variant_id
                );

                if ($order->branch_id) {
                    $stockQuery->where('branch_id', $order->branch_id);
                } else {
                    $stockQuery->whereNull('branch_id');
                }


                $productStock = $stockQuery->lockForUpdate()->first();

                $materialCost = $order->ingredients->sum(
                    fn($ingredient) =>
                        (float) $ingredient->pivot->quantity_used *
                        (float) $ingredient->pivot->unit_cost_used
                );

                $laborCost = $order->users->sum(
                    fn($user) =>
                        (float) $user->pivot->labor_hours *
                        (float) ($user->pivot->labor_rate_per_hour ?? $user->wage_rate_per_hour)
                );

                $totalCost = $materialCost + $laborCost + (float) $order->bop_cost;

                $newQuantity = (float) $order->quantity;

                if ($newQuantity <= 0) {
                    throw new \RuntimeException('Jumlah produksi harus lebih dari nol.');
                }

                $newUnitCost = round($totalCost / $newQuantity, 2);

                if ($productStock) {
                    $oldQuantity = (float) $productStock->stock;
                    $oldUnitCost = $productStock->average_unit_cost;

                    if ($oldQuantity > 0 && $oldUnitCost === null) {
                        throw new \RuntimeException(
                            'HPP saldo awal produk belum ditentukan. Lengkapi biaya stok lama terlebih dahulu.'
                        );
                    }

                    $totalQuantity = $oldQuantity + $newQuantity;

                    $averageUnitCost = (
                        ($oldQuantity * (float) $oldUnitCost) +
                        ($newQuantity * $newUnitCost)
                    ) / $totalQuantity;

                    $productStock->update([
                        'stock' => $totalQuantity,
                        'average_unit_cost' => round($averageUnitCost, 2),
                    ]);
                } else {
                    ProductVariantStock::create([
                        'product_variant_id' => $order->product_variant_id,
                        'branch_id' => $order->branch_id,
                        'stock' => $newQuantity,
                        'average_unit_cost' => $newUnitCost,
                    ]);
                }

                $order->update([
                    'hpp_per_unit' => $newUnitCost,
                    'status' => 'completed',
                ]);

            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Produksi gagal diselesaikan.'
            );
        }

        return back()->with(
            'success',
            'Produksi selesai. Stok bahan baku berkurang dan stok produk jadi bertambah.'
        );
    }

    public function applyRecommendedPrice(Request $request, ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        if (!$this->isOwner()) {
            abort(403);
        }

        if ($productionOrder->status !== 'completed') {
            return back()->with('error', 'Harga hanya bisa ditetapkan dari produksi selesai.');
        }

        $data = $request->validate([
            'final_price' => ['required', 'integer', 'min:1', 'max:9999999999999'],
        ], [
            'final_price.required' => 'Harga pilihan Owner wajib diisi.',
            'final_price.integer' => 'Harga jual harus berupa bilangan bulat dalam rupiah.',
            'final_price.min' => 'Harga jual harus lebih dari Rp0.',
        ]);

        if ($productionOrder->hpp_per_unit <= 0) {
            return back()->with('error', 'HPP produksi tidak valid. Harga tidak diubah.');
        }

        // Pastikan varian tetap berasal dari bakery yang sedang login.
        $variant = ProductVariant::whereKey($productionOrder->product_variant_id)
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })->firstOrFail();

        $variant->update(['price' => $data['final_price']]);

        return back()->with(
            'success',
            'Harga pilihan Owner berhasil disimpan di Produk dan berlaku di POS untuk seluruh cabang.'
        );
    }

    private function isOwner(): bool
    {
        return auth()->user()->role?->name === 'Owner';
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        $this->authorizeTenant($productionOrder);

        if ($productionOrder->status !== 'planned') {
            return redirect()
                ->route('production_orders.index')
                ->with('error', 'Hanya produksi berstatus direncanakan yang dapat dihapus.');
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
        $variants = ProductVariant::with(['product', 'recipe'])
            ->whereHas('product', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
            ->whereHas('recipe')
            ->get();

        $variantOptions = $variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => $variant->name,
                'recipe_quantity' => (float) $variant->recipe->quantity,
                'recipe_unit' => $variant->recipe->unit,
                'production_time' => (float) $variant->recipe->production_time,
            ];
        })->values();

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

        $products = Product::where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $jobDescriptions = JobDescription::where(
            'tenant_id',
            $this->tenantId()
        )
            ->orderBy('name')
            ->get();

        return compact(
            'products',
            'variants',
            'variantOptions',
            'branches',
            'users',
            'jobDescriptions'
        );
    }

    private function validateRequest(Request $request): array
    {
        if (!$this->isOwner()) {
            abort_if(!auth()->user()->branch_id, 403, 'Akun karyawan belum memiliki cabang.');
            $request->merge(['branch_id' => auth()->user()->branch_id]);
        }
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
                'integer',
                'min:1'
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
                'exists:users,id',
                'distinct'
            ],

            'employees.*.job_description' => [
                'required',
                'string',
                Rule::exists('job_descriptions', 'name')
                    ->where('tenant_id', $this->tenantId())
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
        if (!$this->isOwner()) {
            abort_if((int) $id !== (int) auth()->user()->branch_id, 403);
        }
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

    private function findRecipeIngredients($recipe, $branchId)
    {
        $recipeIngredients = $recipe->ingredients;
        $result = collect();
        if ($recipeIngredients->isEmpty()) {
            throw ValidationException::withMessages(['product_variant_id' => 'Resep belum memiliki bahan baku.']);
        }

        foreach ($recipeIngredients as $standard) {
            if (!$standard->ingredient_master_id) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Bahan resep belum terhubung ke master bahan. Jalankan migrasi terlebih dahulu.'
                ]);
            }

            $candidates = \App\Models\Ingredient::where('tenant_id', $this->tenantId())
                ->where('ingredient_master_id', $standard->ingredient_master_id);
            if ($branchId === null) {
                $candidates->whereNull('branch_id');
            } else {
                $candidates->where('branch_id', $branchId);
            }
            $matches = $candidates->get();
            if ($matches->count() !== 1) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Bahan ' . $standard->name . ' harus tersedia tepat satu kali pada cabang produksi.'
                ]);
            }
            $ingredient = $matches->first();
            $ingredient->setRelation('pivot', $standard->pivot);
            $result->push($ingredient);
        }
        return $result;
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
                    'labor_rate_per_hour' =>
                        $users[$employee['user_id']]->wage_rate_per_hour,
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


    private function completeCustomProduction(ProductionOrder $productionOrder)
    {
        try {
            DB::transaction(function () use ($productionOrder) {
                $order = ProductionOrder::whereKey($productionOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->order_type !== 'custom' || $order->status !== 'in_progress') {
                    throw new \RuntimeException('Status produksi tidak sesuai.');
                }

                if (!$order->customOrder) {
                    throw new \RuntimeException('Custom Order tidak ditemukan.');
                }

                $order->load(['ingredients', 'users']);

                if ($order->ingredients->isEmpty() || $order->users->isEmpty()) {
                    throw new \RuntimeException('Data bahan atau tenaga kerja belum lengkap.');
                }

                $materialCost = 0;
                $laborCost = 0;

                foreach ($order->ingredients as $ingredient) {
                    $quantity = (float) $ingredient->pivot->quantity_used;
                    $unitCost = (float) $ingredient->pivot->unit_cost_used;

                    if ($quantity < 0 || $unitCost < 0) {
                        throw new \RuntimeException('Data biaya bahan tidak valid.');
                    }

                    $materialCost += $quantity * $unitCost;
                }

                foreach ($order->users as $user) {
                    $hours = (float) $user->pivot->labor_hours;
                    $rate = (float) $user->pivot->labor_rate_per_hour;

                    if ($hours < 0 || $rate < 0) {
                        throw new \RuntimeException('Data tenaga kerja tidak valid.');
                    }

                    $laborCost += $hours * $rate;
                }

                if (
                    $order->ingredients->every(
                        fn($ingredient) =>
                            (float) $ingredient->pivot->quantity_used <= 0
                    )
                ) {
                    throw new \RuntimeException('Pemakaian bahan aktual belum dicatat.');
                }

                if (
                    $order->users->every(
                        fn($user) =>
                            (float) $user->pivot->labor_hours <= 0
                    )
                ) {
                    throw new \RuntimeException('Jam kerja aktual belum dicatat.');
                }

                $lockedIngredients = [];

                foreach ($order->ingredients->sortBy('id') as $ingredient) {
                    $lockedIngredient = Ingredient::whereKey($ingredient->id)
                        ->where('tenant_id', $order->customOrder->tenant_id)
                        ->where('branch_id', $order->branch_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $quantity = (float) $ingredient->pivot->quantity_used;

                    if ((float) $lockedIngredient->stock < $quantity) {
                        throw new \RuntimeException(
                            'Stok ' . $lockedIngredient->name . ' tidak mencukupi.'
                        );
                    }

                    $lockedIngredients[] = [
                        'ingredient' => $lockedIngredient,
                        'quantity' => $quantity,
                    ];
                }

                foreach ($lockedIngredients as $item) {
                    if ($item['quantity'] > 0) {
                        $item['ingredient']->decrement('stock', $item['quantity']);
                    }
                }

                $bopCost = (float) $order->bop_cost;
                $totalCost = $materialCost + $laborCost + $bopCost;

                if ($order->quantity <= 0) {
                    throw new \RuntimeException('Jumlah produksi tidak valid.');
                }

                $order->update([
                    'hpp_per_unit' => round($totalCost / $order->quantity, 2),
                    'status' => 'completed',
                ]);
            });

            return back()->with(
                'success',
                'Produksi Custom selesai. Stok bahan berkurang dan HPP final tersimpan.'
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Produksi Custom gagal diselesaikan.'
            );
        }
    }
}