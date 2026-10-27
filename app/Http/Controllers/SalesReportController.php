<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CustomOrder;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    private function isOwner(): bool
    {
        return auth()->user()->role && strcasecmp(auth()->user()->role->name, 'Owner') === 0;
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date'   => ['nullable', 'date_format:Y-m-d'],
            'branch_id'  => ['nullable', 'integer'],
            'source'     => ['nullable', 'in:all,pos,custom'],
        ]);

        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $filters['end_date'] ?? now()->toDateString();
        $source    = $filters['source'] ?? 'all';

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return back()->withErrors(['end_date' => 'Tanggal akhir tidak boleh sebelum tanggal awal.'])->withInput();
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
            if ($branchId === null) {
                // Tidak menampilkan transaksi lintas cabang saat penugasan pengguna belum lengkap.
                abort(403, 'Akun belum terhubung ke cabang.');
            }
            if (!$branches->contains('id', (int) $branchId)) {
                abort(403, 'Cabang pengguna tidak valid.');
            }
        }

        $rows = collect();

        if ($source !== 'custom') {
            // Penjualan POS hanya diakui setelah pembayaran terkonfirmasi.
            $sales = Sale::query()
                ->with('branch:id,name')
                ->where('tenant_id', $tenantId)
                ->where('payment_status', 'paid')
                ->whereBetween('sale_date', [$startDate, $endDate]);

            if ($branchId !== null) {
                $sales->where('branch_id', $branchId);
            }

            foreach ($sales->orderBy('sale_date')->orderBy('id')->get() as $sale) {
                $rows->push([
                    'date' => $sale->sale_date->format('Y-m-d'),
                    'sort_id' => $sale->id,
                    'type' => 'POS',
                    'reference' => 'POS-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                    'description' => 'Penjualan regular',
                    'branch' => $sale->branch?->name ?? 'Tanpa cabang',
                    'amount' => (float) $sale->total_amount,
                    'url' => route('sales.show', $sale),
                ]);
            }
        }

        if ($source !== 'pos') {
            // Custom Order diakui SATU KALI ketika pesanan sudah diserahkan.
            // Jangan menjumlahkan DP dan pelunasan sebagai penjualan tambahan.
            $customOrders = CustomOrder::query()
                ->with('branch:id,name')
                ->where('tenant_id', $tenantId)
                ->where('status', 'delivered')
                ->whereNotNull('delivered_at')
                ->whereDate('delivered_at', '>=', $startDate)
                ->whereDate('delivered_at', '<=', $endDate);

            if ($branchId !== null) {
                $customOrders->where('branch_id', $branchId);
            }

            foreach ($customOrders->orderBy('delivered_at')->orderBy('id')->get() as $order) {
                $rows->push([
                    'date' => $order->delivered_at->format('Y-m-d'),
                    'sort_id' => $order->id,
                    'type' => 'Custom',
                    'reference' => $order->order_number,
                    'description' => $order->product_name,
                    'branch' => $order->branch?->name ?? 'Tanpa cabang',
                    'amount' => (float) $order->selling_price,
                    'url' => route('custom_orders.show', $order),
                ]);
            }
        }

        $rows = $rows->sort(fn ($a, $b) => strcmp($b['date'], $a['date']) ?: ($b['sort_id'] <=> $a['sort_id']))->values();
        $posTotal = $rows->where('type', 'POS')->sum('amount');
        $customTotal = $rows->where('type', 'Custom')->sum('amount');
        $grandTotal = $posTotal + $customTotal;
        $posCount = $rows->where('type', 'POS')->count();
        $customCount = $rows->where('type', 'Custom')->count();

        return view('reports.sales', compact(
            'rows', 'branches', 'isOwner', 'branchId', 'startDate', 'endDate',
            'source', 'posTotal', 'customTotal', 'grandTotal', 'posCount', 'customCount'
        ));
    }
}
