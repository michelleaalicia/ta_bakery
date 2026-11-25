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
        $isOwner = strcasecmp($user->role?->name ?? '', 'Owner') === 0;

        $validated = $request->validate([
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'product_id' => 'nullable|integer',
            'product_variant_id' => 'nullable|integer',
            'branch_id' => 'nullable|string',
            'horizon' => 'nullable|integer|min:1|max:7',
            'alpha' => ['nullable', 'regex:/^(auto|0\.[1-9])$/'],
        ]);

        $frequency = $validated['frequency'] ?? 'daily';
        $maxHorizon = match ($frequency) {
            'daily' => 7,
            'weekly' => 4,
            'monthly' => 3,
        };
        $horizon = (int) ($validated['horizon'] ?? 1);
        if ($horizon > $maxHorizon) {
            throw ValidationException::withMessages([
                'horizon' => 'Maksimal prediksi untuk periode ' . match ($frequency) {
                    'daily' => 'harian adalah 7 hari.',
                    'weekly' => 'mingguan adalah 4 minggu.',
                    'monthly' => 'bulanan adalah 3 bulan.',
                },
            ]);
        }
        $alphaChoice = $validated['alpha'] ?? 'auto';
        $branches = Branch::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $hasBranches = $branches->isNotEmpty();
        $branchChoice = $isOwner ? (string) ($validated['branch_id'] ?? 'all') : (string) ($user->branch_id ?? '');
        if ($isOwner && $branchChoice !== 'all' && !$branches->contains('id', (int) $branchChoice)) {
            abort(403, 'Cabang tidak valid.');
        }
        if (!$isOwner && $hasBranches && !$branches->contains('id', (int) $branchChoice)) {
            abort(403, 'Akun belum terdaftar pada cabang yang valid.');
        }

        $products = ProductVariant::query()
            ->whereHas('product', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('product:id,name')
            ->orderBy('product_id')->orderBy('name')
            ->get(['id', 'product_id', 'name']);
        $productOptions = $products->pluck('product')->filter()->unique('id')->sortBy('name')->values();
        $productId = (int) ($validated['product_id'] ?? ($products->first()?->product_id ?? 0));
        if ($productId && !$productOptions->contains('id', $productId)) {
            abort(404, 'Produk tidak ditemukan.');
        }
        $variantsForProduct = $products->where('product_id', $productId)->values();
        $variantId = (int) ($validated['product_variant_id'] ?? ($variantsForProduct->first()?->id ?? 0));
        if ($variantId && !$variantsForProduct->contains('id', $variantId)) {
            abort(404, 'Varian tidak ditemukan.');
        }
        $selectedVariant = $products->firstWhere('id', $variantId);

        // Only completed calendar periods: yesterday, last Sunday, or last day of previous month.
        $today = Carbon::today();
        $latestEnd = match ($frequency) {
            'daily' => $today->copy()->subDay(),
            'weekly' => $today->copy()->startOfWeek(Carbon::MONDAY)->subDay(),
            default => $today->copy()->startOfMonth()->subDay(),
        };
        $defaultStart = match ($frequency) {
            'daily' => $latestEnd->copy()->subDays(59),
            'weekly' => $latestEnd->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(15),
            default => $latestEnd->copy()->startOfMonth()->subMonthsNoOverflow(11),
        };
        // Periode historis ditentukan server; parameter tanggal dari URL diabaikan.
        // Ini menghindari input tanggal tidak valid dan menjaga periode tetap lengkap.
        $start = $defaultStart->copy();
        $end = $latestEnd->copy();

        $labels = [];
        $periodDates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = match ($frequency) {
                'daily' => $cursor->format('Y-m-d'),
                'weekly' => $cursor->format('o-\\WW'), // ISO week-year, e.g. 2026-W46
                default => $cursor->format('Y-m'),
            };
            $labels[] = $key;
            $periodDates[] = $cursor->copy();
            if (count($labels) > 366) {
                throw ValidationException::withMessages(['start_date' => 'Maksimal 366 periode historis.']);
            }
            match ($frequency) {
                'daily' => $cursor->addDay(),
                'weekly' => $cursor->addWeek(),
                default => $cursor->addMonthNoOverflow(),
            };
        }

        // Kunci periode tetap untuk logika; label hanya untuk tampilan pengguna.
        $formatPeriod = static function (string $period) use ($frequency): array {
            if ($frequency === 'daily') {
                $date = Carbon::parse($period)->locale('id');
                return [
                    'short' => $date->translatedFormat('d M'),
                    'full' => $date->translatedFormat('d F Y'),
                ];
            }
            if ($frequency === 'monthly') {
                $date = Carbon::createFromFormat('!Y-m', $period)->locale('id');
                return [
                    'short' => $date->translatedFormat('M Y'),
                    'full' => $date->translatedFormat('F Y'),
                ];
            }

            // Contoh: 2026-W48 -> Senin 23 Nov s.d. Minggu 29 Nov 2026.
            [$year, $week] = array_map('intval', explode('-W', $period));
            $firstDay = Carbon::now()->setISODate($year, $week, 1)->startOfDay()->locale('id');
            $lastDay = $firstDay->copy()->addDays(6)->locale('id');
            if ($firstDay->isSameMonth($lastDay)) {
                return [
                    'short' => $firstDay->format('d') . '-' . $lastDay->translatedFormat('d M'),
                    'full' => $firstDay->format('d') . '-' . $lastDay->translatedFormat('d F Y'),
                ];
            }
            return [
                'short' => $firstDay->translatedFormat('d M') . '-' . $lastDay->translatedFormat('d M'),
                'full' => $firstDay->translatedFormat('d F Y') . ' - ' . $lastDay->translatedFormat('d F Y'),
            ];
        };

        $result = null;
        $history = [];
        $message = null;
        $observedDates = 0;
        if ($selectedVariant && count($labels) >= 7) {
            $query = DB::table('sales_has_product_variants as d')
                ->join('sales as s', 's.id', '=', 'd.sale_id')
                ->where('s.tenant_id', $tenantId)
                ->where('s.payment_status', 'paid')
                ->where('d.product_variant_id', $variantId)
                ->whereDate('s.sale_date', '>=', $start->toDateString())
                ->whereDate('s.sale_date', '<=', $end->toDateString());
            if ($branchChoice !== 'all') {
                if (!$hasBranches && !$isOwner) {
                    $query->whereNull('s.branch_id');
                } else {
                    $query->where('s.branch_id', (int) $branchChoice);
                }
            }
            $sales = $query->select('s.sale_date', 'd.quantity')->get();
            $aggregates = [];
            foreach ($sales as $sale) {
                $date = Carbon::parse($sale->sale_date);
                $key = match ($frequency) {
                    'daily' => $date->format('Y-m-d'),
                    'weekly' => $date->format('o-\\WW'),
                    default => $date->format('Y-m'),
                };
                $aggregates[$key] = ($aggregates[$key] ?? 0) + (float) $sale->quantity;
            }
            $observedDates = count($aggregates);
            $values = array_map(fn($label) => $aggregates[$label] ?? 0, $labels);
            if ($observedDates >= 7) {
                $result = $alphaChoice === 'auto'
                    ? $des->bestAlpha($values, $horizon)
                    : $des->calculate($values, (float) $alphaChoice, $horizon);
                foreach ($result['rows'] as $index => $row) {
                    $row['period'] = $labels[$index];
                    $periodLabel = $formatPeriod($row['period']);
                    $row['period_short'] = $periodLabel['short'];
                    $row['period_label'] = $periodLabel['full'];
                    $history[] = $row;
                }
                foreach ($result['future'] as &$item) {
                    $futureDate = $end->copy();
                    match ($frequency) {
                        'daily' => $futureDate->addDays($item['step']),
                        'weekly' => $futureDate->addWeeks($item['step'])->startOfWeek(Carbon::MONDAY),
                        default => $futureDate->addMonthsNoOverflow($item['step'])->startOfMonth(),
                    };
                    $item['period'] = match ($frequency) {
                        'daily' => $futureDate->format('Y-m-d'),
                        'weekly' => $futureDate->format('o-\\WW'),
                        default => $futureDate->format('Y-m'),
                    };
                    $periodLabel = $formatPeriod($item['period']);
                    $item['period_short'] = $periodLabel['short'];
                    $item['period_label'] = $periodLabel['full'];
                }
                unset($item);
            } else {
                $message = 'Data belum cukup: hanya ' . $observedDates . ' dari ' . count($labels) . ' periode historis memiliki penjualan. Diperlukan minimal 7 periode dengan penjualan. Untuk demo Bulanan, masukkan data dummy 12 bulan terlebih dahulu.';
            }
        } elseif (count($labels) < 7) {
            $message = 'Gunakan minimal 7 periode historis lengkap, termasuk periode dengan penjualan nol.';
        } else {
            $message = 'Belum ada varian produk untuk dihitung.';
        }

        $chartHistory = array_map(fn($row) => ['period' => $row['period'], 'actual' => $row['actual']], $history);
        $chartFuture = $result ? array_map(fn($row) => ['period' => $row['period'], 'units' => $row['units']], $result['future']) : [];
        $todayDate = $today->toDateString();

        return view('forecasts.index', compact(
            'branches',
            'hasBranches',
            'branchChoice',
            'isOwner',
            'products',
            'productOptions',
            'productId',
            'variantId',
            'selectedVariant',
            'frequency',
            'horizon',
            'maxHorizon',
            'alphaChoice',
            'start',
            'end',
            'latestEnd',
            'result',
            'history',
            'message',
            'observedDates',
            'chartHistory',
            'chartFuture',
            'todayDate'
        ));
    }
}
