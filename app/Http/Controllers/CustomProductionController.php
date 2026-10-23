<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CustomOrder;
use App\Models\Ingredient;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomProductionController extends Controller
{
    public function store(CustomOrder $customOrder)
    {
        $this->authorizeOrder($customOrder);

        $productionOrder = DB::transaction(function () use ($customOrder) {
            $order = CustomOrder::whereKey($customOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan harus dikonfirmasi terlebih dahulu.',
                ]);
            }

            if ($order->production_order_id !== null) {
                throw ValidationException::withMessages([
                    'production' => 'Pesanan ini sudah memiliki produksi.',
                ]);
            }

            if (!$order->payments()->where('payment_type', 'dp')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran DP harus dicatat sebelum produksi dibuat.',
                ]);
            }

            $order->load(['materials', 'labors']);

            if ($order->materials->isEmpty()) {
                throw ValidationException::withMessages([
                    'materials' => 'Rencana bahan baku belum tersedia.',
                ]);
            }

            if ($order->labors->isEmpty()) {
                throw ValidationException::withMessages([
                    'labors' => 'Rencana tenaga kerja belum tersedia.',
                ]);
            }

            if ($order->estimated_bop_cost === null) {
                throw ValidationException::withMessages([
                    'bop' => 'Estimasi BOP belum ditentukan.',
                ]);
            }

            $masterIds = $order->materials
                ->pluck('ingredient_master_id');

            $ingredients = Ingredient::where(
                'tenant_id',
                $order->tenant_id
            )
                ->where('branch_id', $order->branch_id)
                ->whereIn('ingredient_master_id', $masterIds)
                ->get()
                ->keyBy('ingredient_master_id');

            if ($ingredients->count() !== $masterIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'materials' => 'Ada bahan baku yang tidak tersedia di cabang pesanan.',
                ]);
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
                'notes' => 'Custom Order '
                    . $order->order_number
                    . ' - '
                    . $order->product_name,
            ]);

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
                'notes' => 'Custom Order '
                    . $order->order_number
                    . ' - '
                    . $order->product_name,
            ]);

            foreach ($order->materials as $material) {
                $ingredient = $ingredients->get(
                    $material->ingredient_master_id
                );

                $production->ingredients()->attach(
                    $ingredient->id,
                    [
                        'quantity_used' => 0,
                        'unit_cost_used' => $ingredient->unit_cost,
                    ]
                );
            }

            foreach ($order->labors as $labor) {
                $production->users()->attach(
                    $labor->user_id,
                    [
                        'job_description' => $labor->job_description,
                        'status' => 'assigned',
                        'labor_hours' => 0,
                        'labor_rate_per_hour' => $labor->estimated_rate,
                    ]
                );
            }

            foreach ($order->labors as $labor) {
                $production->users()->attach(
                    $labor->user_id,
                    [
                        'job_description' => $labor->job_description,
                        'status' => 'assigned',
                        'labor_hours' => 0,
                        'labor_rate_per_hour' => $labor->estimated_rate,
                    ]
                );
            }

            $order->update([
                'production_order_id' => $production->id,
            ]);

            return $production;
        });

        return redirect()
            ->route('production_orders.show', $productionOrder)
            ->with('success', 'Produksi Custom berhasil dibuat.');
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


    public function updateActuals(
        Request $request,
        ProductionOrder $productionOrder
    ) {
        if ($productionOrder->order_type !== 'custom') {
            abort(404);
        }

        $productionOrder->load('customOrder');
        $this->authorizeOrder($productionOrder->customOrder);

        if ($productionOrder->status !== 'in_progress') {
            return back()->with(
                'error',
                'Biaya aktual hanya bisa diubah saat produksi berlangsung.'
            );
        }

        $data = $request->validate([
            'materials' => ['required', 'array'],
            'materials.*' => ['required', 'numeric', 'min:0'],
            'labors' => ['required', 'array'],
            'labors.*' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($productionOrder, $data) {
            $order = ProductionOrder::whereKey($productionOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $order->order_type !== 'custom' ||
                $order->status !== 'in_progress'
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Status produksi sudah berubah.',
                ]);
            }

            $order->load(['ingredients', 'users']);

            foreach ($order->ingredients as $ingredient) {
                $id = $ingredient->id;

                if (!array_key_exists($id, $data['materials'])) {
                    throw ValidationException::withMessages([
                        'materials' => 'Data pemakaian bahan tidak lengkap.',
                    ]);
                }

                $order->ingredients()->updateExistingPivot(
                    $id,
                    [
                        'quantity_used' => $data['materials'][$id],
                    ]
                );
            }

            foreach ($order->users as $user) {
                $id = $user->id;

                if (!array_key_exists($id, $data['labors'])) {
                    throw ValidationException::withMessages([
                        'labors' => 'Data jam kerja tidak lengkap.',
                    ]);
                }

                $order->users()->updateExistingPivot(
                    $id,
                    [
                        'labor_hours' => $data['labors'][$id],
                    ]
                );
            }
        });

        return redirect()
            ->route('production_orders.show', $productionOrder)
            ->with('success', 'Biaya aktual berhasil disimpan.');
    }
}
