<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;

class JobCostSheetController extends Controller
{
    public function show(ProductionOrder $productionOrder)
    {
        $user = auth()->user();

        abort_unless($productionOrder->order_type === 'custom', 404);

        $productionOrder->load([
            'branch',
            'customOrder.tenant',
            'customOrder.branch',
            'customOrder.materials.ingredientMaster',
            'customOrder.labors.user',
            'ingredients',
            'users',
        ]);

        $customOrder = $productionOrder->customOrder;

        abort_unless($customOrder, 404);
        abort_unless((int) $customOrder->tenant_id === (int) $user->tenant_id, 403);

        if ($user->role?->name !== 'Owner') {
            abort_unless((int) $customOrder->branch_id === (int) $user->branch_id, 403);
        }

        abort_unless((int) $customOrder->production_order_id === (int) $productionOrder->id, 404);

        $estimateMaterial = (float) $customOrder->materials->sum('estimated_total_cost');
        $estimateLabor = (float) $customOrder->labors->sum('estimated_cost');
        $estimateBop = (float) ($customOrder->estimated_bop_cost ?? 0);
        $estimateTotal = $estimateMaterial + $estimateLabor + $estimateBop;

        $actualMaterial = (float) $productionOrder->ingredients->sum(
            fn ($ingredient) => (float) $ingredient->pivot->quantity_used * (float) $ingredient->pivot->unit_cost_used
        );

        $actualLabor = (float) $productionOrder->users->sum(
            fn ($employee) => (float) $employee->pivot->labor_hours * (float) ($employee->pivot->labor_rate_per_hour ?? 0)
        );

        $actualBop = (float) $productionOrder->bop_cost;
        $actualTotal = $actualMaterial + $actualLabor + $actualBop;
        $quantity = (float) $productionOrder->quantity;
        $estimateHpp = $quantity > 0 ? $estimateTotal / $quantity : 0;
        $actualHpp = $quantity > 0 ? $actualTotal / $quantity : 0;

        $isPlanned = $productionOrder->status === 'planned';
        $isCompleted = $productionOrder->status === 'completed';
        $sellingPrice = $customOrder->selling_price !== null ? (float) $customOrder->selling_price : null;
        $displayTotal = $isPlanned ? $estimateTotal : $actualTotal;
        $grossProfit = $sellingPrice !== null ? $sellingPrice - $displayTotal : null;
        $grossMargin = $sellingPrice !== null && $sellingPrice > 0
            ? $grossProfit / $sellingPrice * 100
            : null;

        return view('job_cost_sheets.show', compact(
            'productionOrder', 'customOrder', 'estimateMaterial', 'estimateLabor', 'estimateBop',
            'estimateTotal', 'actualMaterial', 'actualLabor', 'actualBop', 'actualTotal',
            'estimateHpp', 'actualHpp', 'quantity', 'isPlanned', 'isCompleted',
            'sellingPrice', 'displayTotal', 'grossProfit', 'grossMargin'
        ));
    }
}
