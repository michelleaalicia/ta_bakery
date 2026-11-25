<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
use App\Models\Ingredient;
use App\Models\ProductionOrder;
use App\Models\Sale;
use App\Support\DemoSaleFilter;
use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\ProductVariantStock;
use App\Services\BrownDesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, BrownDesService $des)
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

        $salesQuery = DemoSaleFilter::exclude(Sale::query())
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

        // Batas peringatan: sampai 20% di atas minimum stok.
        // Termasuk stok tepat di minimum maupun di bawah minimum.
        // Bahan dengan minimum 0 dianggap belum memiliki batas peringatan.
        $lowStockQuery = (clone $ingredientsQuery)
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereRaw('stock <= min_stock * 1.20');

        $lowStockCount = (clone $lowStockQuery)->count();

        $recentProductions = (clone $productionQuery)
            ->with(['branch:id,name', 'productVariant.product', 'customOrder'])
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $activeCustomOrders = (clone $customQuery)
            ->with('branch:id,name')
            ->where('status', 'confirmed')
            ->orderBy('required_date')
            ->orderBy('id')
            ->get();

        $lowStockIngredients = (clone $lowStockQuery)
            ->with(['branch:id,name', 'master:id,name,unit'])
            ->orderBy('branch_id')
            ->get()->sortBy(fn($ingredient) => $ingredient->master?->name)->values();


        // Rekomendasi produksi BESOK: data DES memakai 60 hari penuh sampai KEMARIN.
        // Karena hari ini belum lengkap, besok adalah horizon 2, bukan horizon 1.
        $forecastBranches = Branch::where('tenant_id', $tenantId)
            ->orderBy('name')->get(['id', 'name']);
        $hasForecastBranches = $forecastBranches->isNotEmpty();

        if ($isOwner) {
            $selectedForecastBranchId = $hasForecastBranches
                ? (int) $request->query('forecast_branch_id', $forecastBranches->first()->id)
                : null;
            if ($hasForecastBranches && !$forecastBranches->contains('id', $selectedForecastBranchId)) {
                abort(403, 'Cabang forecast tidak valid.');
            }
        } else {
            $selectedForecastBranchId = $branchId === null ? null : (int) $branchId;
        }

        $forecastTomorrow = Carbon::today()->addDay()->format('d/m/Y');
        $forecastRows = [];
        $forecastChartVariants = [];
        $forecastEligibleCount = 0;
        $forecastHistoricalDays = 60;
        $forecastMinSellingDays = 7;
        $forecastStart = Carbon::yesterday()->subDays($forecastHistoricalDays - 1);
        $forecastEnd = Carbon::yesterday();

        // Batasi pilihan varian ke tenant dan produk yang masih aktif.
        $variants = ProductVariant::query()
            ->where('status', 'active')
            ->whereHas('product', fn($q) => $q
                ->where('tenant_id', $tenantId)
                ->where('status', 'active'))
            ->with('product:id,name')
            ->get(['id', 'product_id', 'name']);

        if ($variants->isNotEmpty()) {
            $variantIds = $variants->pluck('id')->all();

            // Satu pengambilan penjualan untuk semua varian. Hanya penjualan POS lunas.
            $dailySales = DB::table('sales_has_product_variants as d')
                ->join('sales as s', 's.id', '=', 'd.sale_id')
                ->where('s.tenant_id', $tenantId)
                ->where('s.payment_status', 'paid')
                ->whereIn('d.product_variant_id', $variantIds)
                ->whereDate('s.sale_date', '>=', $forecastStart->toDateString())
                ->whereDate('s.sale_date', '<=', $forecastEnd->toDateString())
                ->when(
                    $selectedForecastBranchId !== null,
                    fn($q) => $q->where('s.branch_id', $selectedForecastBranchId),
                    fn($q) => $q->whereNull('s.branch_id')
                )
                ->select('d.product_variant_id', 's.sale_date', DB::raw('SUM(d.quantity) as total_qty'))
                ->groupBy('d.product_variant_id', 's.sale_date')
                ->get();

            $dailyByVariant = [];
            foreach ($dailySales as $saleRow) {
                $key = Carbon::parse($saleRow->sale_date)->toDateString();
                $dailyByVariant[(int) $saleRow->product_variant_id][$key] = (float) $saleRow->total_qty;
            }

            // Stok PER CABANG, agar rekomendasi tidak tercampur dengan stok cabang lain.
            $stockQuery = ProductVariantStock::query()
                ->whereIn('product_variant_id', $variantIds)
                ->when(
                    $selectedForecastBranchId !== null,
                    fn($q) => $q->where('branch_id', $selectedForecastBranchId),
                    fn($q) => $q->whereNull('branch_id')
                )
                ->select('product_variant_id', DB::raw('SUM(stock) as total_stock'))
                ->groupBy('product_variant_id')
                ->get()
                ->pluck('total_stock', 'product_variant_id');

            foreach ($variants as $variant) {
                $salesByDay = $dailyByVariant[$variant->id] ?? [];
                // Sama dengan halaman Forecast: hari tanpa transaksi dihitung nol.
                // Syarat tambahan untuk dashboard: minimal 7 hari benar-benar ada penjualan.
                if (count($salesByDay) < $forecastMinSellingDays) {
                    continue;
                }

                $values = [];
                $cursor = $forecastStart->copy();
                while ($cursor->lte($forecastEnd)) {
                    $values[] = $salesByDay[$cursor->toDateString()] ?? 0.0;
                    $cursor->addDay();
                }

                $desResult = $des->bestAlpha($values, 2);
                $forecastUnits = (int) $desResult['future'][1]['units'];
                $stock = max(0, (float) ($stockQuery[$variant->id] ?? 0));
                $recommended = max(0, $forecastUnits - (int) floor($stock));

                $forecastEligibleCount++;

                // Data grafik disimpan untuk semua varian yang layak diramal,
                // termasuk yang belum memerlukan produksi tambahan.
                $historyDates = [];
                $historyActual = [];
                for ($day = 30; $day >= 1; $day--) {
                    $date = $forecastEnd->copy()->subDays($day - 1);
                    $historyDates[] = $date->format('d/m');
                    $historyActual[] = $salesByDay[$date->toDateString()] ?? 0.0;
                }
                $forecastChartVariants[] = [
                    'variant_id' => (int) $variant->id,
                    'product_id' => (int) $variant->product_id,
                    'product_name' => $variant->product?->name ?? 'Produk',
                    'variant_name' => $variant->name,
                    'name' => trim(($variant->product?->name ?? 'Produk') . ' - ' . $variant->name),
                    'labels' => array_merge($historyDates, [Carbon::today()->format('d/m'), Carbon::tomorrow()->format('d/m')]),
                    'actual' => array_merge($historyActual, [null, null]),
                    // Titik awal garis putus-putus adalah aktual terakhir agar grafik tersambung.
                    'prediction' => array_merge(array_fill(0, 29, null), [
                        end($historyActual),
                        (int) $desResult['future'][0]['units'],
                        $forecastUnits
                    ]),
                    'forecast' => $forecastUnits,
                    'stock' => $stock,
                    'recommended' => $recommended,
                    'alpha' => $desResult['alpha'],
                    'mae' => $desResult['mae'],
                    'mape' => $desResult['mape'],
                ];

                if ($recommended <= 0) {
                    continue;
                }

                $forecastRows[] = [
                    'name' => trim(($variant->product?->name ?? 'Produk') . ' - ' . $variant->name),
                    'variant_id' => $variant->id,
                    'forecast' => $forecastUnits,
                    'stock' => $stock,
                    'recommended' => $recommended,
                    'alpha' => $desResult['alpha'],
                ];
            }

            usort($forecastRows, fn($a, $b) =>
                ($b['recommended'] <=> $a['recommended']) ?: strcmp($a['name'], $b['name']));
            $forecastRows = array_slice($forecastRows, 0, 5);
        }

        $forecastMaxRecommended = max([1, ...array_column($forecastRows, 'recommended')]);
        usort($forecastChartVariants, fn($a, $b) =>
            ($b['recommended'] <=> $a['recommended']) ?: strcmp($a['name'], $b['name']));
        // Filter berantai Cabang -> Produk -> Varian. Semua opsi berasal dari
        // varian tenant/cabang yang telah memenuhi syarat data historis DES.
        $forecastProducts = collect($forecastChartVariants)
            ->map(fn($row) => [
                'product_id' => $row['product_id'],
                'name' => $row['product_name'],
            ])
            ->unique('product_id')
            ->sortBy('name')
            ->values()
            ->all();

        $requestedProductId = (int) $request->query('forecast_product_id', 0);
        $requestedVariantId = (int) $request->query('forecast_variant_id', 0);

        // Agar URL lama dengan forecast_variant_id saja tetap berfungsi.
        $requestedVariant = collect($forecastChartVariants)->firstWhere('variant_id', $requestedVariantId);
        $selectedForecastProductId = collect($forecastProducts)
            ->contains('product_id', $requestedProductId)
            ? $requestedProductId
            : ($requestedVariant['product_id'] ?? ($forecastProducts[0]['product_id'] ?? null));

        $forecastVariantsForProduct = collect($forecastChartVariants)
            ->where('product_id', $selectedForecastProductId)
            ->sortBy('variant_name')
            ->values()
            ->all();

        $forecastChart = collect($forecastVariantsForProduct)->firstWhere('variant_id', $requestedVariantId)
            ?? ($forecastVariantsForProduct[0] ?? null);
        $selectedForecastVariantId = $forecastChart['variant_id'] ?? null;
        $forecastBranchName = $selectedForecastBranchId === null
            ? 'Bakery tanpa cabang'
            : ($forecastBranches->firstWhere('id', $selectedForecastBranchId)?->name ?? 'Cabang');

        return view('dashboard', compact(
            'isOwner',
            'revenueToday',
            'activeProductionsCount',
            'activeCustomOrdersCount',
            'lowStockCount',
            'recentProductions',
            'activeCustomOrders',
            'lowStockIngredients',
            'forecastBranches',
            'hasForecastBranches',
            'selectedForecastBranchId',
            'forecastBranchName',
            'forecastTomorrow',
            'forecastRows',
            'forecastEligibleCount',
            'forecastMaxRecommended',
            'forecastChartVariants',
            'forecastProducts',
            'forecastVariantsForProduct',
            'selectedForecastProductId',
            'forecastChart',
            'selectedForecastVariantId'
        ));
    }
}
