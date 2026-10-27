<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SaleHistoryController extends Controller
{
    private function scopedSales()
    {
        $user = auth()->user();

        return Sale::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($user->role?->name !== 'Owner', function ($query) use ($user) {
                if ($user->branch_id === null) {
                    abort(403);
                }

                $query->where('branch_id', $user->branch_id);
            });
    }

    private function getAuthorizedSale(Sale $sale): Sale
    {
        return $this->scopedSales()
            ->with(['tenant', 'branch', 'details.productVariant.product'])
            ->findOrFail($sale->id);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:paid,pending,cancelled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $sales = $this->scopedSales()
            ->with('branch')
            ->withCount('details')
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('payment_status', $status))
            ->when($filters['from'] ?? null, fn($query, $date) => $query->whereDate('sale_date', '>=', $date))
            ->when($filters['to'] ?? null, fn($query, $date) => $query->whereDate('sale_date', '<=', $date))
            ->orderByDesc('id')
            ->get();

        return view('sales.index', compact('sales', 'filters'));
    }

    public function show(Sale $sale)
    {
        $sale = $this->getAuthorizedSale($sale);

        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale = $this->getAuthorizedSale($sale);

        abort_unless($sale->payment_status === 'paid', 404);

        return Pdf::loadView('sales.receipt', compact('sale'))
            ->setPaper('a5', 'portrait')
            ->stream('nota-pos-' . $sale->id . '.pdf');
    }
}