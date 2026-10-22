<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CustomOrder;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\JobDescription;

class CustomOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $orders = CustomOrder::with('branch')
            ->where('tenant_id', $user->tenant_id)
            ->when($user->role?->name !== 'Owner', function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id);
            })
            ->latest()
            ->get();

        return view('custom_orders.index', compact('orders'));
    }

    public function create()
    {
        $user = auth()->user();

        $branches = Branch::where('tenant_id', $user->tenant_id)
            ->when($user->role?->name !== 'Owner', function ($query) use ($user) {
                $query->where('id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();

        $branchIds = $branches->pluck('id');

        $ingredients = Ingredient::where('tenant_id', $user->tenant_id)
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('ingredient_master_id')
            ->orderBy('name')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'master_id' => $ingredient->ingredient_master_id,
                    'branch_id' => $ingredient->branch_id,
                    'name' => $ingredient->name,
                    'unit' => $ingredient->unit,
                    'stock' => (float) $ingredient->stock,
                    'unit_cost' => (float) $ingredient->unit_cost,
                ];
            })
            ->values();

        return view('custom_orders.create', compact('branches', 'ingredients'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->where('tenant_id', $user->tenant_id),
            ],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['required', 'string', 'max:50'],
            'product_name' => ['required', 'string', 'max:255'],
            'specification' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'required_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:order_date',
            ],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.ingredient_master_id' => ['required', 'integer'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        if (
            $user->role?->name !== 'Owner' &&
            (int) $data['branch_id'] !== (int) $user->branch_id
        ) {
            abort(403);
        }

        $branch = Branch::where('tenant_id', $user->tenant_id)
            ->findOrFail($data['branch_id']);

        $masterIds = collect($data['materials'])
            ->pluck('ingredient_master_id');

        if ($masterIds->unique()->count() !== $masterIds->count()) {
            throw ValidationException::withMessages([
                'materials' => 'Bahan baku tidak boleh dipilih lebih dari sekali.',
            ]);
        }

        $ingredients = Ingredient::where('tenant_id', $user->tenant_id)
            ->where('branch_id', $branch->id)
            ->whereIn('ingredient_master_id', $masterIds)
            ->get();

        if (
            $ingredients->count() !== $masterIds->count() ||
            $ingredients->pluck('ingredient_master_id')->unique()->count() !== $masterIds->count()
        ) {
            throw ValidationException::withMessages([
                'materials' => 'Bahan baku tidak valid atau tidak tersedia pada cabang pilihan.',
            ]);
        }

        $ingredients = $ingredients->keyBy('ingredient_master_id');

        $order = DB::transaction(function () use ($data, $user, $branch, $ingredients) {
            $order = CustomOrder::create([
                'tenant_id' => $user->tenant_id,
                'branch_id' => $branch->id,
                'production_order_id' => null,
                'order_number' => 'TMP-' . Str::upper(Str::random(16)),
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'],
                'product_name' => $data['product_name'],
                'specification' => $data['specification'],
                'quantity' => $data['quantity'],
                'order_date' => $data['order_date'],
                'required_date' => $data['required_date'],
                'selling_price' => null,
                'status' => 'draft',
            ]);

            $order->update([
                'order_number' => 'CO-'
                    . date('Y', strtotime($data['order_date']))
                    . '-'
                    . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            ]);

            foreach ($data['materials'] as $material) {
                $ingredient = $ingredients->get($material['ingredient_master_id']);

                $quantity = (float) $material['quantity'];
                $unitCost = (float) $ingredient->unit_cost;

                $order->materials()->create([
                    'ingredient_master_id' => $ingredient->ingredient_master_id,
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_total_cost' => round($quantity * $unitCost, 2),
                ]);
            }

            return $order;
        });

        return redirect()
            ->route('custom_orders.show', $order)
            ->with(
                'success',
                'Custom Order ' . $order->order_number . ' berhasil disimpan sebagai draft.'
            );
    }


    public function edit(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);
        $this->authorizeOwner();

        if ($customOrder->status !== 'draft') {
            return redirect()
                ->route('custom_orders.show', $customOrder)
                ->with('error', 'Hanya pesanan Draft yang dapat diedit.');
        }

        $customOrder->load('materials');

        $tenantId = auth()->user()->tenant_id;

        $branches = Branch::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::where('tenant_id', $tenantId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->whereNotNull('ingredient_master_id')
            ->orderBy('name')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'master_id' => $ingredient->ingredient_master_id,
                    'branch_id' => $ingredient->branch_id,
                    'name' => $ingredient->name,
                    'unit' => $ingredient->unit,
                    'stock' => (float) $ingredient->stock,
                    'unit_cost' => (float) $ingredient->unit_cost,
                ];
            })
            ->values();

        return view('custom_orders.edit', compact(
            'customOrder',
            'branches',
            'ingredients'
        ));
    }

    public function update(Request $request, CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);
        $this->authorizeOwner();

        $user = auth()->user();

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->where('tenant_id', $user->tenant_id),
            ],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['required', 'string', 'max:50'],
            'product_name' => ['required', 'string', 'max:255'],
            'specification' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'order_date' => ['required', 'date_format:Y-m-d'],
            'required_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:order_date',
            ],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.ingredient_master_id' => ['required', 'integer'],
            'materials.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $masterIds = collect($data['materials'])
            ->pluck('ingredient_master_id');

        if ($masterIds->unique()->count() !== $masterIds->count()) {
            throw ValidationException::withMessages([
                'materials' => 'Bahan baku tidak boleh dipilih lebih dari sekali.',
            ]);
        }

        $ingredients = Ingredient::where('tenant_id', $user->tenant_id)
            ->where('branch_id', $data['branch_id'])
            ->whereIn('ingredient_master_id', $masterIds)
            ->get();

        if (
            $ingredients->count() !== $masterIds->count() ||
            $ingredients->pluck('ingredient_master_id')->unique()->count() !== $masterIds->count()
        ) {
            throw ValidationException::withMessages([
                'materials' => 'Bahan tidak valid atau tidak tersedia di cabang pilihan.',
            ]);
        }

        $ingredients = $ingredients->keyBy('ingredient_master_id');

        DB::transaction(function () use ($customOrder, $data, $ingredients) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan tidak lagi berstatus Draft.',
                ]);
            }

            $order->update([
                'branch_id' => $data['branch_id'],
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'],
                'product_name' => $data['product_name'],
                'specification' => $data['specification'],
                'quantity' => $data['quantity'],
                'order_date' => $data['order_date'],
                'required_date' => $data['required_date'],
                'selling_price' => null,
            ]);

            $order->labors()->delete();

            $order->update([
                'estimated_bop_cost' => null,
            ]);

            $order->materials()->delete();

            foreach ($data['materials'] as $material) {
                $ingredient = $ingredients->get(
                    $material['ingredient_master_id']
                );

                $quantity = (float) $material['quantity'];
                $unitCost = (float) $ingredient->unit_cost;

                $order->materials()->create([
                    'ingredient_master_id' => $ingredient->ingredient_master_id,
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_total_cost' => round($quantity * $unitCost, 2),
                ]);
            }
        });

        return redirect()
            ->route('custom_orders.show', $customOrder)
            ->with(
                'success',
                'Custom Order berhasil diperbarui.'
            );
    }


    public function show(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);

        $customOrder->load([
            'branch',
            'materials.ingredientMaster',
            'labors.user',
            'payments',
        ]);

        $materialCost = (float) $customOrder->materials->sum('estimated_total_cost');
        $laborCost = (float) $customOrder->labors->sum('estimated_cost');
        $overheadCost = (float) ($customOrder->estimated_bop_cost ?? 0);

        $totalCost = $materialCost + $laborCost + $overheadCost;

        $costPerUnit = $customOrder->quantity > 0
            ? $totalCost / $customOrder->quantity
            : 0;

        $sellingPrice = (float) ($customOrder->selling_price ?? 0);

        $estimatedProfit = $customOrder->selling_price !== null
            ? $sellingPrice - $totalCost
            : null;

        $estimatedMargin = $estimatedProfit !== null && $sellingPrice > 0
            ? $estimatedProfit / $sellingPrice * 100
            : null;

        $employees = User::where('tenant_id', $customOrder->tenant_id)
            ->where('branch_id', $customOrder->branch_id)
            ->where('status', 'active')
            ->whereHas('role', fn($query) => $query->where('name', 'Produksi'))
            ->orderBy('name')
            ->get(['id', 'name', 'wage_rate_per_hour']);

        $jobDescriptions = JobDescription::where('tenant_id', $customOrder->tenant_id)
            ->orderBy('name')
            ->get();

        return view('custom_orders.show', compact(
            'customOrder',
            'materialCost',
            'laborCost',
            'overheadCost',
            'totalCost',
            'costPerUnit',
            'estimatedProfit',
            'estimatedMargin',
            'employees',
            'jobDescriptions'
        ));
    }


    public function updateEstimation(Request $request, CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);
        $this->authorizeOwner();

        $data = $request->validate([
            'labors' => ['required', 'array', 'min:1'],
            'labors.*.user_id' => ['required', 'integer', 'distinct'],
            'labors.*.job_description' => [
                'required',
                'string',
                Rule::exists('job_descriptions', 'name')
                    ->where('tenant_id', $customOrder->tenant_id),
            ],
            'labors.*.estimated_hours' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999',
            ],
            'estimated_bop_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999',
            ],
        ]);

        DB::transaction(function () use ($customOrder, $data) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan Draft yang dapat diubah.',
                ]);
            }

            $userIds = collect($data['labors'])->pluck('user_id');

            $users = User::where('tenant_id', $order->tenant_id)
                ->where('branch_id', $order->branch_id)
                ->where('status', 'active')
                ->whereHas('role', fn($query) => $query->where('name', 'Produksi'))
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            if ($users->count() !== $userIds->count()) {
                throw ValidationException::withMessages([
                    'labors' => 'Karyawan tidak valid atau tidak sesuai cabang.',
                ]);
            }

            foreach ($users as $user) {
                if (
                    $user->wage_rate_per_hour === null ||
                    (float) $user->wage_rate_per_hour <= 0
                ) {
                    throw ValidationException::withMessages([
                        'labors' => 'Tarif upah ' . $user->name . ' belum valid.',
                    ]);
                }
            }

            $order->labors()->delete();

            foreach ($data['labors'] as $labor) {
                $user = $users->get($labor['user_id']);

                $hours = (float) $labor['estimated_hours'];
                $rate = (float) $user->wage_rate_per_hour;

                $order->labors()->create([
                    'user_id' => $user->id,
                    'job_description' => $labor['job_description'],
                    'estimated_hours' => $hours,
                    'estimated_rate' => $rate,
                    'estimated_cost' => round($hours * $rate, 2),
                ]);
            }

            $order->update([
                'estimated_bop_cost' => $data['estimated_bop_cost'],
                'selling_price' => null,
            ]);
        });

        return back()->with(
            'success',
            'Estimasi BTKL dan BOP berhasil disimpan. Harga penawaran perlu ditetapkan ulang.'
        );
    }

    public function updatePrice(Request $request, CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);
        $this->authorizeOwner();

        if ($customOrder->status !== 'draft') {
            return back()->with(
                'error',
                'Harga hanya dapat diubah saat pesanan masih draft.'
            );
        }

        if (
            !$customOrder->labors()->exists() ||
            $customOrder->estimated_bop_cost === null
        ) {
            return back()->with(
                'error',
                'Lengkapi estimasi tenaga kerja dan BOP terlebih dahulu.'
            );
        }

        $data = $request->validate([
            'selling_price' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999999999999',
            ],
        ]);

        $customOrder->update([
            'selling_price' => $data['selling_price'],
        ]);

        return back()->with(
            'success',
            'Harga penawaran berhasil disimpan.'
        );
    }


    public function confirm(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);
        $this->authorizeOwner();

        DB::transaction(function () use ($customOrder) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan Draft yang dapat dikonfirmasi.',
                ]);
            }

            if (!$order->materials()->exists()) {
                throw ValidationException::withMessages([
                    'materials' => 'Rencana bahan baku belum lengkap.',
                ]);
            }

            if (!$order->labors()->exists()) {
                throw ValidationException::withMessages([
                    'labors' => 'Estimasi tenaga kerja belum lengkap.',
                ]);
            }

            if ($order->estimated_bop_cost === null) {
                throw ValidationException::withMessages([
                    'estimated_bop_cost' => 'Estimasi BOP belum ditentukan.',
                ]);
            }

            if ($order->selling_price === null || $order->selling_price <= 0) {
                throw ValidationException::withMessages([
                    'selling_price' => 'Harga penawaran belum ditetapkan.',
                ]);
            }

            $order->update([
                'status' => 'confirmed',
            ]);
        });

        return redirect()
            ->route('custom_orders.show', $customOrder)
            ->with('success', 'Pesanan berhasil dikonfirmasi.');
    }

    private function authorizeOrder(CustomOrder $customOrder): void
    {
        $user = auth()->user();

        if ((int) $customOrder->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }

        if (
            $user->role?->name !== 'Owner' &&
            (int) $customOrder->branch_id !== (int) $user->branch_id
        ) {
            abort(403);
        }
    }

    private function authorizeOwner(): void
    {
        if (auth()->user()->role?->name !== 'Owner') {
            abort(403);
        }
    }
}
