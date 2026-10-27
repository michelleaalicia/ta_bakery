<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use App\Models\Ingredient;
use App\Models\ProductionOrder;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user()->loadMissing('role');
        $tenantId = $user->tenant_id;
        $isOwner = $user->role?->name === 'Owner';
        $branchId = $isOwner ? null : $user->branch_id;

        // Pengguna non-Owner hanya boleh melihat data cabangnya sendiri.
        if (!$isOwner && $branchId === null) {
            abort(403, 'Akun belum terhubung ke cabang.');
        }

        $today = now()->toDateString();

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenantId)
            ->when(!$isOwner, fn($query) => $query->where('branch_id', $branchId));

        $customQuery = CustomOrder::query()
            ->where('tenant_id', $tenantId)
            ->when(!$isOwner, fn($query) => $query->where('branch_id', $branchId));

        // ProductionOrder tidak mempunyai tenant_id: batasi via relasi branch.
        $productionQuery = ProductionOrder::query()
            ->whereHas('branch', fn($query) => $query->where('tenant_id', $tenantId))
            ->when(!$isOwner, fn($query) => $query->where('branch_id', $branchId));

        $ingredientsQuery = Ingredient::query()
            ->where('tenant_id', $tenantId)
            ->when(!$isOwner, fn($query) => $query->where('branch_id', $branchId));

        // POS: hanya pembayaran lunas. Custom: diakui saat diserahkan,
        // bukan saat DP/pelunasan diterima, agar tidak dihitung dua kali.
        $posRevenueToday = (clone $salesQuery)
            ->where('payment_status', 'paid')
            ->whereDate('sale_date', $today)
            ->sum('total_amount');

        $customRevenueToday = (clone $customQuery)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', $today)
            ->sum('selling_price');

        $revenueToday = $posRevenueToday + $customRevenueToday;

        $activeProductionsCount = (clone $productionQuery)
            ->where('status', 'in_progress')
            ->count();

        $activeCustomOrdersCount = (clone $customQuery)
            ->where('status', 'confirmed')
            ->count();

        // Stok yang menyentuh batas minimum juga dianggap menipis.
        $lowStockQuery = (clone $ingredientsQuery)
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('stock', '<=', 'min_stock');

        $lowStockCount = (clone $lowStockQuery)->count();

        $recentProductions = (clone $productionQuery)
            ->with(['branch:id,name', 'productVariant.product', 'customOrder'])
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $activeCustomOrders = (clone $customQuery)
            ->with('branch:id,name')
            ->where('status', 'confirmed')
            ->orderBy('required_date')
            ->orderBy('id')
            ->limit(30)
            ->get();

        $lowStockIngredients = (clone $lowStockQuery)
            ->with('branch:id,name')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return view('dashboard', compact(
            'revenueToday',
            'activeProductionsCount',
            'activeCustomOrdersCount',
            'lowStockCount',
            'recentProductions',
            'activeCustomOrders',
            'lowStockIngredients'
        ));
    }
}
