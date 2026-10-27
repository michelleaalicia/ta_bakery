<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use App\Models\Ingredient;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomProductionController extends Controller
{
    public function store(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);

        $productionOrder = DB::transaction(function () use ($customOrder) {
            $order = CustomOrder::whereKey($customOrder->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'confirmed') {
                throw ValidationException::withMessages(['status' => 'Pesanan harus dikonfirmasi terlebih dahulu.']);
            }
            if ($order->production_order_id !== null) {
                throw ValidationException::withMessages(['production' => 'Pesanan ini sudah memiliki produksi.']);
            }
            if ($order->payments()->sum('amount') <= 0) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran awal harus dicatat sebelum produksi dibuat.']);
            }

            $order->load(['materials', 'labors']);
            if ($order->materials->isEmpty() || $order->labors->isEmpty()) {
                throw ValidationException::withMessages(['production' => 'Rencana bahan dan tenaga kerja harus tersedia.']);
            }
            if ($order->estimated_bop_cost === null) {
                throw ValidationException::withMessages(['bop' => 'Estimasi BOP belum ditentukan.']);
            }

            $masterIds = $order->materials->pluck('ingredient_master_id');
            $ingredients = Ingredient::where('tenant_id', $order->tenant_id)
                ->where('branch_id', $order->branch_id)
                ->whereIn('ingredient_master_id', $masterIds)
                ->get()->keyBy('ingredient_master_id');

            if ($ingredients->count() !== $masterIds->unique()->count()) {
                throw ValidationException::withMessages(['materials' => 'Ada bahan baku yang tidak tersedia di cabang pesanan.']);
            }

            $production = ProductionOrder::create([
                'product_variant_id' => null,
                'branch_id' => $order->branch_id,
                'batch_count' => 1,
                'order_type' => 'custom',
                'quantity' => $order->quantity,
                'production_date' => now()->toDateString(),
                'status' => 'planned',
                'bop_cost' => $order->estimated_bop_cost,
                'hpp_per_unit' => 0,
                'notes' => 'Custom Order ' . $order->order_number . ' - ' . $order->product_name,
            ]);

            // Nol di pivot adalah aktual yang belum dicatat, bukan estimasi.
            foreach ($order->materials as $material) {
                $ingredient = $ingredients->get($material->ingredient_master_id);
                $production->ingredients()->attach($ingredient->id, [
                    'quantity_used' => 0,
                    'unit_cost_used' => $ingredient->unit_cost,
                ]);
            }

            foreach ($order->labors as $labor) {
                $production->users()->attach($labor->user_id, [
                    'job_description' => $labor->job_description,
                    'status' => 'assigned',
                    'labor_hours' => 0,
                    'labor_rate_per_hour' => $labor->estimated_rate,
                ]);
            }

            $order->update(['production_order_id' => $production->id]);
            return $production;
        });

        return redirect()->route('production_orders.show', $productionOrder)
            ->with('success', 'Produksi Custom berhasil dibuat.');
    }

    public function updateActuals(Request $request, ProductionOrder $productionOrder)
    {
        if ($productionOrder->order_type !== 'custom')
            abort(404);
        $productionOrder->load('customOrder');
        if (!$productionOrder->customOrder)
            abort(404);
        $this->authorizeOrder($productionOrder->customOrder);

        if ($productionOrder->status !== 'in_progress') {
            return back()->with('error', 'Biaya aktual hanya bisa diubah saat produksi berlangsung.');
        }

        $data = $request->validate([
            'materials' => ['required', 'array', 'min:1'],
            'materials.*' => ['required', 'numeric', 'min:0'],
            'labors' => ['required', 'array', 'min:1'],
            'labors.*' => ['required', 'numeric', 'min:0'],
            'extra_materials' => ['nullable', 'array'],
            'extra_materials.*.ingredient_id' => ['required', 'integer', 'distinct'],
            'extra_materials.*.quantity' => ['required', 'numeric', 'gt:0'],
            'extra_labors' => ['nullable', 'array'],
            'extra_labors.*.user_id' => ['required', 'integer', 'distinct'],
            'extra_labors.*.job_description' => ['required', 'string', 'max:255'],
            'extra_labors.*.labor_hours' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($productionOrder, $data) {
            $order = ProductionOrder::whereKey($productionOrder->id)->lockForUpdate()->firstOrFail();
            if ($order->order_type !== 'custom' || $order->status !== 'in_progress') {
                throw ValidationException::withMessages(['status' => 'Status produksi sudah berubah.']);
            }
            $custom = CustomOrder::where('production_order_id', $order->id)->firstOrFail();
            $order->load(['ingredients', 'users']);

            $existingMaterials = $order->ingredients->pluck('id')->map(fn($id) => (int) $id)->all();
            $existingUsers = $order->users->pluck('id')->map(fn($id) => (int) $id)->all();

            foreach ($order->ingredients as $ingredient) {
                $id = $ingredient->id;
                if (!array_key_exists($id, $data['materials'])) {
                    throw ValidationException::withMessages(['materials' => 'Data pemakaian bahan tidak lengkap.']);
                }
                $order->ingredients()->updateExistingPivot($id, [
                    'quantity_used' => $data['materials'][$id],
                ]);
            }

            foreach ($order->users as $user) {
                $id = $user->id;
                if (!array_key_exists($id, $data['labors'])) {
                    throw ValidationException::withMessages(['labors' => 'Data jam kerja tidak lengkap.']);
                }
                // Penanda bahwa form aktual telah disimpan, termasuk bila nilainya memang nol.
                $order->users()->updateExistingPivot($id, [
                    'labor_hours' => $data['labors'][$id],
                    'updated_at' => now(),
                ]);
            }

            $extraMaterials = collect($data['extra_materials'] ?? []);
            $extraMaterialIds = $extraMaterials->pluck('ingredient_id')->map(fn($id) => (int) $id);
            if ($extraMaterialIds->intersect($existingMaterials)->isNotEmpty()) {
                throw ValidationException::withMessages(['extra_materials' => 'Bahan tambahan sudah ada dalam produksi. Ubah jumlah pada baris yang tersedia.']);
            }
            $materials = Ingredient::where('tenant_id', $custom->tenant_id)
                ->where('branch_id', $order->branch_id)
                ->whereNotNull('ingredient_master_id')
                ->whereIn('id', $extraMaterialIds)->get()->keyBy('id');
            if ($materials->count() !== $extraMaterialIds->count()) {
                throw ValidationException::withMessages(['extra_materials' => 'Bahan tambahan harus tersedia di cabang produksi.']);
            }
            foreach ($extraMaterials as $item) {
                $ingredient = $materials->get($item['ingredient_id']);
                $order->ingredients()->attach($ingredient->id, [
                    'quantity_used' => $item['quantity'],
                    'unit_cost_used' => $ingredient->unit_cost,
                ]);
            }

            $extraLabors = collect($data['extra_labors'] ?? []);
            $extraUserIds = $extraLabors->pluck('user_id')->map(fn($id) => (int) $id);
            if ($extraUserIds->intersect($existingUsers)->isNotEmpty()) {
                throw ValidationException::withMessages(['extra_labors' => 'Karyawan sudah ditugaskan. Ubah jam kerja pada baris yang tersedia.']);
            }
            $users = User::where('tenant_id', $custom->tenant_id)
                ->where('branch_id', $order->branch_id)
                ->where('status', 'active')
                ->whereHas('role', fn($q) => $q->where('name', 'Produksi'))
                ->whereIn('id', $extraUserIds)->get()->keyBy('id');
            if ($users->count() !== $extraUserIds->count()) {
                throw ValidationException::withMessages(['extra_labors' => 'Karyawan tambahan harus aktif dan sesuai cabang produksi.']);
            }
            $jobNames = \App\Models\JobDescription::where('tenant_id', $custom->tenant_id)->pluck('name')->all();
            foreach ($extraLabors as $item) {
                $user = $users->get($item['user_id']);
                if (!in_array($item['job_description'], $jobNames, true)) {
                    throw ValidationException::withMessages(['extra_labors' => 'Pekerjaan tambahan tidak tersedia dalam daftar pekerjaan.']);
                }
                if ($user->wage_rate_per_hour === null || (float) $user->wage_rate_per_hour <= 0) {
                    throw ValidationException::withMessages(['extra_labors' => 'Tarif upah ' . $user->name . ' belum valid.']);
                }
                $order->users()->attach($user->id, [
                    'job_description' => $item['job_description'],
                    'status' => 'in_progress',
                    'labor_hours' => $item['labor_hours'],
                    'labor_rate_per_hour' => $user->wage_rate_per_hour,
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('production_orders.show', $productionOrder)
            ->with('success', 'Biaya aktual berhasil disimpan.');
    }

    private function authorizeOrder(CustomOrder $customOrder): void
    {
        $user = auth()->user();
        if ((int) $customOrder->tenant_id !== (int) $user->tenant_id)
            abort(403);
        if ($user->role?->name !== 'Owner' && (int) $customOrder->branch_id !== (int) $user->branch_id)
            abort(403);
    }
}
