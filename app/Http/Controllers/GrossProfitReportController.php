<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CustomOrder;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GrossProfitReportController extends Controller
{
    private function isOwner(): bool
    {
        return auth()->user()->role && strcasecmp(auth()->user()->role->name, 'Owner') === 0;
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'in:all,pos,custom'],
        ]);
        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();
        $source = $filters['source'] ?? 'all';
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return back()->withErrors(['end_date' => 'Tanggal akhir tidak boleh lebih awal.'])->withInput();
        }

        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $isOwner = $this->isOwner();
        $branches = Branch::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        if ($isOwner) {
            $branchId = $filters['branch_id'] ?? null;
            if ($branchId !== null && !$branches->contains('id', (int) $branchId)) {
                return back()->withErrors(['branch_id' => 'Cabang tidak valid.'])->withInput();
            }
        } else {
            $branchId = $user->branch_id;
            if ($branchId === null || !$branches->contains('id', (int) $branchId)) {
                abort(403, 'Akun belum terhubung ke cabang valid.');
            }
        }

        $rows = collect();
        if ($source !== 'custom') {
            $sales = Sale::with(['branch:id,name', 'details'])
                ->where('tenant_id', $tenantId)->where('payment_status', 'paid')
                ->whereBetween('sale_date', [$startDate, $endDate]);
            if ($branchId !== null) $sales->where('branch_id', $branchId);
            foreach ($sales->orderBy('sale_date')->orderBy('id')->get() as $sale) {
                $missing = $sale->details->isEmpty() || $sale->details->contains(fn ($detail) => $detail->unit_cost === null);
                $cost = $missing ? null : $sale->details->sum(fn ($detail) => (float) $detail->quantity * (float) $detail->unit_cost);
                $revenue = (float) $sale->total_amount;
                $rows->push([
                    'date' => $sale->sale_date->format('Y-m-d'), 'id' => $sale->id,
                    'source' => 'POS', 'reference' => 'POS-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                    'branch' => $sale->branch?->name ?? 'Tanpa cabang',
                    'revenue' => $revenue, 'cost' => $cost, 'gross' => $cost === null ? null : $revenue - $cost,
                    'url' => route('sales.show', $sale),
                ]);
            }
        }

        if ($source !== 'pos') {
            $orders = CustomOrder::with(['branch:id,name', 'productionOrder'])
                ->where('tenant_id', $tenantId)->where('status', 'delivered')
                ->whereNotNull('delivered_at')
                ->whereDate('delivered_at', '>=', $startDate)
                ->whereDate('delivered_at', '<=', $endDate);
            if ($branchId !== null) $orders->where('branch_id', $branchId);
            foreach ($orders->orderBy('delivered_at')->orderBy('id')->get() as $order) {
                $production = $order->productionOrder;
                $valid = $production && $production->order_type === 'custom'
                    && $production->status === 'completed' && $production->hpp_per_unit !== null
                    && (float) $production->quantity > 0;
                $cost = $valid ? (float) $production->hpp_per_unit * (float) $production->quantity : null;
                $revenue = (float) $order->selling_price;
                $rows->push([
                    'date' => $order->delivered_at->format('Y-m-d'), 'id' => $order->id,
                    'source' => 'Custom', 'reference' => $order->order_number,
                    'branch' => $order->branch?->name ?? 'Tanpa cabang',
                    'revenue' => $revenue, 'cost' => $cost, 'gross' => $cost === null ? null : $revenue - $cost,
                    'url' => route('custom_orders.show', $order),
                ]);
            }
        }

        $rows = $rows->sort(fn ($a, $b) => strcmp($b['date'], $a['date']) ?: ($b['id'] <=> $a['id']))->values();
        $revenueTotal = $rows->sum('revenue');
        $costKnownTotal = $rows->whereNotNull('cost')->sum('cost');
        $missingCount = $rows->whereNull('cost')->count();
        // Jangan tampilkan 'laba kotor total' sebagai final jika ada HPP yang belum diketahui.
        $grossTotal = $missingCount === 0 ? $revenueTotal - $costKnownTotal : null;
        return view('reports.gross-profit', compact('rows', 'branches', 'branchId', 'isOwner', 'startDate', 'endDate', 'source', 'revenueTotal', 'costKnownTotal', 'missingCount', 'grossTotal'));
    }
}
