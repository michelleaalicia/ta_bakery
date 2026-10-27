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
        if ($user->role?->name !== 'Owner') {
            abort_if(!$user->branch_id, 403, 'Akun karyawan belum memiliki cabang.');
            $request->merge(['branch_id' => $user->branch_id]);
        }

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
        if ($user->role?->name !== 'Owner') {
            abort_if(!$user->branch_id, 403, 'Akun karyawan belum memiliki cabang.');
            $request->merge(['branch_id' => $user->branch_id]);
        }

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

        $requiresPriceReview = false;

        DB::transaction(function () use ($customOrder, $data, $ingredients, &$requiresPriceReview) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan tidak lagi berstatus Draft.',
                ]);
            }

            // Jangan pindahkan estimasi tenaga kerja ke cabang lain secara diam-diam.
            // Karyawan yang telah dipilih adalah karyawan cabang semula.
            if ((int) $order->branch_id !== (int) $data['branch_id']
                && $order->labors()->exists()) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Cabang tidak bisa diubah setelah estimasi tenaga kerja diisi. Pertahankan cabang ini agar penugasan karyawan tetap valid.',
                ]);
            }

            // Tandai kebutuhan peninjauan harga, tanpa menghapus harga yang sudah ada.
            $oldMaterials = $order->materials()->get()
                ->mapWithKeys(fn ($item) => [
                    (string) $item->ingredient_master_id => [
                        'quantity' => round((float) $item->quantity, 3),
                        'cost' => round((float) $item->estimated_unit_cost, 2),
                    ],
                ])->sortKeys()->all();

            $newMaterials = collect($data['materials'])
                ->mapWithKeys(function ($item) use ($ingredients) {
                    $ingredient = $ingredients->get($item['ingredient_master_id']);
                    return [(string) $item['ingredient_master_id'] => [
                        'quantity' => round((float) $item['quantity'], 3),
                        'cost' => round((float) $ingredient->unit_cost, 2),
                    ]];
                })->sortKeys()->all();

            $requiresPriceReview = $oldMaterials !== $newMaterials
                || (int) $order->quantity !== (int) $data['quantity']
                || (int) $order->branch_id !== (int) $data['branch_id']
                || $order->product_name !== $data['product_name']
                || $order->specification !== $data['specification'];

            $order->update([
                'branch_id' => $data['branch_id'],
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'],
                'product_name' => $data['product_name'],
                'specification' => $data['specification'],
                'quantity' => $data['quantity'],
                'order_date' => $data['order_date'],
                'required_date' => $data['required_date'],
            ]);

            // Estimasi BTKL, BOP dan harga jual dipertahankan.
            // Hanya rencana bahan yang diganti berdasarkan edit.

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

        $redirect = redirect()->route('custom_orders.show', $customOrder)
            ->with('success', 'Custom Order berhasil diperbarui. Estimasi tenaga kerja, BOP, dan harga penawaran tetap tersimpan.');

        if ($requiresPriceReview && $customOrder->selling_price !== null) {
            $redirect->with('error', 'Rencana biaya atau spesifikasi berubah. Periksa kembali HPP dan harga penawaran sebelum mengonfirmasi pesanan. Harga sebelumnya tetap tersimpan.');
        }

        return $redirect;
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

        $customOrder->loadMissing(['productionOrder', 'payments']);

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
            // Satu karyawan boleh mengerjakan beberapa pekerjaan.
            'labors.*.user_id' => ['required', 'integer'],
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

        // Duplikasi karyawan + pekerjaan tidak boleh, tetapi karyawan
        // dengan beberapa pekerjaan berbeda tetap diperbolehkan.
        $pairs = collect($data['labors'])
            ->map(fn($labor) => $labor['user_id'] . '|' . $labor['job_description']);

        if ($pairs->count() !== $pairs->unique()->count()) {
            throw ValidationException::withMessages([
                'labors' => 'Karyawan dan pekerjaan yang sama tidak boleh ditambahkan dua kali.',
            ]);
        }

        DB::transaction(function () use ($customOrder, $data) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan Draft yang dapat diubah.',
                ]);
            }

            // Pivot produksi hanya punya satu baris untuk setiap user.
            // Karena itu beberapa job digabung ke satu record estimasi.
            $groupedLabors = collect($data['labors'])->groupBy('user_id');
            $userIds = $groupedLabors->keys();

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

            $preparedLabors = [];

            foreach ($groupedLabors as $userId => $tasks) {
                $user = $users->get($userId);
                $rate = (float) $user->wage_rate_per_hour;

                if ($user->wage_rate_per_hour === null || $rate <= 0) {
                    throw ValidationException::withMessages([
                        'labors' => 'Tarif upah ' . $user->name . ' belum valid.',
                    ]);
                }

                // Simpan rincian pekerjaan dan masing-masing jam di kolom
                // job_description agar tetap dapat dibaca tanpa migrasi.
                $description = $tasks->map(function ($task) {
                    $hours = rtrim(rtrim(number_format((float) $task['estimated_hours'], 2, '.', ''), '0'), '.');
                    return $task['job_description'] . ' (' . $hours . ' jam)';
                })->implode('; ');

                if (mb_strlen($description) > 255) {
                    throw ValidationException::withMessages([
                        'labors' => 'Rincian pekerjaan ' . $user->name . ' terlalu panjang. Kurangi jumlah pekerjaan.',
                    ]);
                }

                $hours = round((float) $tasks->sum('estimated_hours'), 2);

                if ($hours <= 0 || $hours > 999999) {
                    throw ValidationException::withMessages([
                        'labors' => 'Total jam kerja ' . $user->name . ' tidak valid.',
                    ]);
                }

                $preparedLabors[] = [
                    'user_id' => $user->id,
                    'job_description' => $description,
                    'estimated_hours' => $hours,
                    'estimated_rate' => $rate,
                    'estimated_cost' => round($hours * $rate, 2),
                ];
            }

            $order->labors()->delete();

            foreach ($preparedLabors as $labor) {
                $order->labors()->create($labor);
            }

            $order->update([
                'estimated_bop_cost' => $data['estimated_bop_cost'],
                'selling_price' => null,
            ]);
        });

        return back()->with(
            'success',
            'Estimasi BTKL dan BOP berhasil disimpan.'
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
