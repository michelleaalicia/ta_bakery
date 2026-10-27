<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductVariant;
use App\Services\BrownDesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForecastController extends Controller
{
    public function index(Request $request, BrownDesService $des)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $isOwner = $user->role && strcasecmp($user->role->name, 'Owner') === 0;

        $validated = $request->validate([
            'frequency' => 'nullable|in:daily,monthly',
            'product_id' => 'nullable|integer',
            'product_variant_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'horizon' => 'nullable|integer|min:1|max:7',
            'alpha' => ['nullable', 'regex:/^(auto|0\.[1-9])$/'],
        ]);

        $frequency = $validated['frequency'] ?? 'daily';
        $horizon = (int) ($validated['horizon'] ?? 3);
        $alphaChoice = $validated['alpha'] ?? 'auto';
        $branches = Branch::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $hasBranches = $branches->isNotEmpty();
        $branchId = $isOwner
            ? ($hasBranches ? (int) ($validated['branch_id'] ?? $branches->first()->id) : null)
            : ($hasBranches ? (int) $user->branch_id : null);

        if ($hasBranches && !$branches->contains('id', $branchId)) {
            abort(403, 'Cabang tidak valid atau tidak tersedia untuk akun ini.');
        }

        $products = ProductVariant::query()
            ->whereHas('product', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('product:id,name')
            ->orderBy('product_id')->orderBy('name')->get(['id', 'product_id', 'name']);

        $productOptions = $products->pluck('product')->filter()->unique('id')->sortBy('name')->values();
        $productId = (int) ($validated['product_id'] ?? ($products->first()?->product_id ?? 0));
        if ($productId && !$productOptions->contains('id', $productId)) {
            abort(404, 'Produk tidak ditemukan.');
        }
        $variantsForProduct = $products->where('product_id', $productId)->values();
        $variantId = (int) ($validated['product_variant_id'] ?? ($variantsForProduct->first()?->id ?? 0));
        if ($variantId && !$variantsForProduct->contains('id', $variantId)) {
            abort(404, 'Varian produk tidak ditemukan.');
        }

        $today = Carbon::today();
        $latestEnd = $frequency === 'daily'
            ? $today->copy()->subDay()
            : $today->copy()->startOfMonth()->subDay();

        $defaultStart = $frequency === 'daily'
            ? $latestEnd->copy()->subDays(29)
            : $latestEnd->copy()->startOfMonth()->subMonths(11);

        $start = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : $defaultStart;
        $end = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $latestEnd;
        if ($frequency === 'monthly') {
            $start->startOfMonth();
            $end->endOfMonth();
        }

        if ($start->gt($end) || $end->gt($latestEnd) || $start->diffInDays($end) > 1100) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang tanggal tidak valid, terlalu panjang, atau memuat periode yang belum selesai.',
            ]);
        }

        $labels = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format($frequency === 'daily' ? 'Y-m-d' : 'Y-m');
            if ($frequency === 'daily') {
                $cursor->addDay();
            } else {
                $cursor->addMonthNoOverflow();
            }
            if (count($labels) > 366) {
                break;
            }
        }

        if (count($labels) > 366) {
            throw ValidationException::withMessages(['start_date' => 'Maksimal 366 periode historis.']);
        }

        $result = null;
        $history = [];
        $message = null;
        $observedDates = 0;
        $selectedVariant = $products->firstWhere('id', $variantId);

        if ($selectedVariant && count($labels) >= 7) {
            $query = DB::table('sales_has_product_variants as d')
                ->join('sales as s', 's.id', '=', 'd.sale_id')
                ->where('s.tenant_id', $tenantId)
                ->where('s.payment_status', 'paid')
                ->where('d.product_variant_id', $variantId)
                ->whereDate('s.sale_date', '>=', $start->toDateString())
                ->whereDate('s.sale_date', '<=', $end->toDateString());

            if ($branchId === null) {
                $query->whereNull('s.branch_id');
            } else {
                $query->where('s.branch_id', $branchId);
            }

            $sales = $query->select('s.sale_date', 'd.quantity')->get();
            $aggregates = [];
            foreach ($sales as $sale) {
                $key = Carbon::parse($sale->sale_date)->format($frequency === 'daily' ? 'Y-m-d' : 'Y-m');
                $aggregates[$key] = ($aggregates[$key] ?? 0) + (float) $sale->quantity;
            }
            $observedDates = count($aggregates);
            $values = array_map(fn($label) => $aggregates[$label] ?? 0, $labels);
            $result = $alphaChoice === 'auto'
                ? $des->bestAlpha($values, $horizon)
                : $des->calculate($values, (float) $alphaChoice, $horizon);

            foreach ($result['rows'] as $index => $row) {
                $row['period'] = $labels[$index];
                $history[] = $row;
            }

            $futureBase = $frequency === 'daily' ? $end->copy()->addDay() : $end->copy()->startOfMonth()->addMonth();
            foreach ($result['future'] as &$item) {
                $item['period'] = ($frequency === 'daily'
                ? $futureBase->copy()->addDays($item['step'] - 1)
                : $futureBase->copy()->addMonthsNoOverflow($item['step'] - 1))
                    ->format($frequency === 'daily' ? 'Y-m-d' : 'Y-m');
            }
            unset($item);

            if ($observedDates === 0) {
                $result = null;
                $history = [];
                $message = 'Belum ada data penjualan untuk produk, cabang, dan periode ini. Coba pilih rentang tanggal atau produk lain.';
            }
        } elseif (count($labels) < 7) {
            $message = 'Perlu minimal 7 periode historis yang lengkap (hari atau bulan), termasuk periode dengan penjualan nol.';
        } else {
            $message = 'Belum ada varian produk regular yang dapat dipilih.';
        }

        return view('forecasts.index', compact(
            'branches',
            'hasBranches',
            'branchId',
            'isOwner',
            'products',
            'productOptions',
            'productId',
            'variantId',
            'selectedVariant',
            'frequency',
            'horizon',
            'alphaChoice',
            'start',
            'end',
            'result',
            'history',
            'message',
            'observedDates'
        ));
    }
}
