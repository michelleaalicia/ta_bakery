@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Penjualan Hari Ini</div>
                    <h4 class="mb-0 fw-semibold">Rp {{ number_format($revenueToday, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Produksi Aktif</div>
                    <h4 class="mb-0 fw-semibold">{{ $activeProductionsCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Custom Order Aktif</div>
                    <h4 class="mb-0 fw-semibold">{{ $activeCustomOrdersCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Stok Bahan Baku Perlu Perhatian</div>
                    <h4 class="mb-0 fw-semibold">{{ $lowStockCount }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik DES menggunakan data 60 hari sampai kemarin; ditampilkan 30 hari terakhir. --}}
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-semibold">Grafik Forecast Penjualan &amp; Rekomendasi Produksi</div>
                <div class="small text-muted">{{ $forecastTomorrow }}</div>
            </div>
            <a href="{{ route('forecasts.index', array_filter(['frequency' => 'daily', 'horizon' => 2, 'branch_id' => $selectedForecastBranchId], fn($v) => $v !== null)) }}"
                class="btn btn-dark btn-sm ms-auto">
                Lihat Semua Forecast
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}" class="row g-3 mb-3 align-items-end"
                id="dashboardForecastFilter">
                @if ($isOwner && $hasForecastBranches)
                    <div class="col-md-4">
                        <label for="forecast_branch_id" class="form-label small mb-1">Cabang</label>
                        <select id="forecast_branch_id" name="forecast_branch_id" class="form-select form-select-sm"
                            onchange="document.getElementById('forecast_product_id').value='';document.getElementById('forecast_variant_id').value='';this.form.submit()">
                            @foreach ($forecastBranches as $forecastBranch)
                                <option value="{{ $forecastBranch->id }}" @selected((int) $selectedForecastBranchId === (int) $forecastBranch->id)>
                                    {{ $forecastBranch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label for="forecast_product_id" class="form-label small mb-1">Produk</label>
                    <select id="forecast_product_id" name="forecast_product_id" class="form-select form-select-sm"
                        onchange="document.getElementById('forecast_variant_id').value='';this.form.submit()"
                        @disabled(empty($forecastProducts))>
                        @forelse ($forecastProducts as $option)
                            <option value="{{ $option['product_id'] }}"
                                @selected($selectedForecastProductId === $option['product_id'])>
                                {{ $option['name'] }}
                            </option>
                        @empty
                            <option value="">Belum ada produk dengan data cukup</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="forecast_variant_id" class="form-label small mb-1">Varian</label>
                    <select id="forecast_variant_id" name="forecast_variant_id" class="form-select form-select-sm"
                        onchange="this.form.submit()" @disabled(empty($forecastVariantsForProduct))>
                        @forelse ($forecastVariantsForProduct as $option)
                            <option value="{{ $option['variant_id'] }}"
                                @selected($selectedForecastVariantId === $option['variant_id'])>
                                {{ $option['variant_name'] }}
                            </option>
                        @empty
                            <option value="">Belum ada varian dengan data cukup</option>
                        @endforelse
                    </select>
                </div>
            </form>

            @if ($forecastChart)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <strong class="small">{{ $forecastChart['name'] }}</strong>
                    <span class="small text-muted">Alpha {{ number_format($forecastChart['alpha'], 1, ',', '.') }} ·
                        MAE {{ number_format($forecastChart['mae'] ?? 0, 2, ',', '.') }}
                        @if ($forecastChart['mape'] !== null)
                            · MAPE {{ number_format($forecastChart['mape'], 2, ',', '.') }}%
                        @endif
                    </span>
                </div>
                <div class="forecast-chart-wrapper">
                    <canvas id="dashboardForecastChart" role="img"
                        aria-label="Grafik penjualan aktual 30 hari terakhir dan prediksi DES hari ini serta besok"></canvas>
                </div>
                <div class="d-flex flex-wrap gap-3 justify-content-center small mt-2 mb-3">
                    <span><span class="forecast-legend forecast-legend-actual"></span> Penjualan aktual</span>
                    <span><span class="forecast-legend forecast-legend-prediction"></span> Prediksi DES</span>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-sm-4">
                        <div class="forecast-metric h-100">
                            <div class="small text-muted">Prediksi Permintaan Besok</div>
                            <div class="fw-bold fs-5">{{ $forecastChart['forecast'] }} pcs</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="forecast-metric h-100">
                            <div class="small text-muted">Stok Tersedia</div>
                            <div class="fw-bold fs-5">
                                {{ rtrim(rtrim(number_format($forecastChart['stock'], 2, ',', '.'), '0'), ',') }} pcs
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="forecast-metric h-100">
                            <div class="small text-muted">Rekomendasi Produksi</div>
                            <div class="fw-bold fs-5">{{ $forecastChart['recommended'] }} pcs</div>
                        </div>
                    </div>
                </div>
                @php
                    $canPlanProduction = auth()->user()->role?->modules?->contains('name', 'Produksi') ?? false;
                @endphp
                @if ($canPlanProduction && (int) $forecastChart['recommended'] > 0)
                    <div class="d-flex justify-content-end mt-3 w-100">
                        <a href="{{ route('production_orders.create', array_filter([
                        'from_forecast' => 1,
                        'branch_id' => $selectedForecastBranchId,
                        'product_variant_id' => $forecastChart['variant_id'],
                        'recommended_qty' => $forecastChart['recommended'],
                    ], fn($value) => $value !== null)) }}" class="btn btn-dark btn-sm">
                            Buat Rencana Produksi
                        </a>
                    </div>
                @endif
            @else
                <div class="text-muted text-center py-4">
                    Data penjualan belum cukup untuk menghasilkan grafik forecast.
                    <div class="small mt-1">Diperlukan minimal 7 hari dengan penjualan dalam 60 hari.</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Produksi Terbaru</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk - Varian</th>
                            <th>Cabang</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentProductions as $production)
                                            @php
                                                $isCustom = $production->order_type === 'custom';
                                                $productLabel = $isCustom
                                                    ? ($production->customOrder?->product_name ?? 'Pesanan Custom')
                                                    : trim(($production->productVariant?->product?->name ?? 'Produk') . ' - ' . ($production->productVariant?->name ?? '-'));
                                                $productionStatus = match ($production->status) {
                                                    'planned' => 'Direncanakan',
                                                    'in_progress' => 'Berlangsung',
                                                    'completed' => 'Selesai',
                                                    'cancelled' => 'Dibatalkan',
                                                    default => ucfirst((string) $production->status),
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $productLabel }}
                                                    @if ($isCustom)
                                                        <span class="badge bg-secondary ms-1">Custom</span>
                                                    @endif
                                                </td>
                                                <td>{{ $production->branch?->name ?? '-' }}</td>
                                                <td>{{ (float) $production->quantity == (int) $production->quantity ? (int) $production->quantity : rtrim(rtrim(number_format((float) $production->quantity, 2, ',', '.'), '0'), ',') }}
                                                </td>
                                                <td class="text-nowrap">
                                                    {{ $production->production_date ? \Illuminate\Support\Carbon::parse($production->production_date)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ match ($production->status) {
                                'completed' => 'bg-success',
                                'in_progress' => 'bg-warning text-dark',
                                'cancelled' => 'bg-danger',
                                default => 'bg-secondary',
                            } }}">{{ $productionStatus }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('production_orders.show', $production) }}"
                                                        class="btn btn-info btn-sm">Detail</a>
                                                </td>
                                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada produksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Custom Order Aktif</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Produk Custom</th>
                            <th>Cabang</th>
                            <th>Tanggal Dibutuhkan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeCustomOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->product_name }}</td>
                                <td>{{ $order->branch?->name ?? '-' }}</td>
                                <td class="text-nowrap">
                                    {{ $order->required_date ? \Illuminate\Support\Carbon::parse($order->required_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td><span class="badge bg-info text-dark">Dikonfirmasi</span></td>
                                <td><a href="{{ route('custom_orders.show', $order) }}" class="btn btn-info btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada custom order aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Stok Bahan Baku Perlu Perhatian</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bahan Baku</th>
                            <th>Cabang</th>
                            <th>Stok</th>
                            <th>Minimum Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockIngredients as $ingredient)
                            @php
                                $displayQuantity = fn($number) => rtrim(rtrim(number_format((float) $number, 3, ',', '.'), '0'), ',');
                            @endphp
                            <tr>
                                <td>{{ $ingredient->master?->name }}</td>
                                <td>{{ $ingredient->branch?->name ?? '-' }}</td>
                                <td>{{ $displayQuantity($ingredient->stock) }} {{ $ingredient->master?->unit }}</td>
                                <td>{{ $displayQuantity($ingredient->min_stock) }} {{ $ingredient->master?->unit }}</td>
                                <td>
                                    @if ((float) $ingredient->stock < (float) $ingredient->min_stock)
                                        <span class="badge bg-danger">Di Bawah Minimum</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Menipis</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Tidak ada bahan baku yang perlu perhatian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .forecast-chart-wrapper {
            position: relative;
            width: 100%;
            height: 355px;
        }

        .forecast-chart-wrapper canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .forecast-metric {
            padding: 12px 14px;
            background: #FAF8F5;
            border: 1px solid #F3E7DA;
            border-radius: 8px;
        }

        .forecast-legend {
            display: inline-block;
            width: 20px;
            height: 0;
            vertical-align: middle;
            margin-right: 4px;
            border-top: 3px solid;
        }

        .forecast-legend-actual {
            border-color: #794330;
        }

        .forecast-legend-prediction {
            border-color: #E09B32;
            border-top-style: dashed;
        }

        .forecast-bar-track {
            height: 12px;
            background-color: #F3E7DA;
            border-radius: 8px;
        }

        .forecast-bar-fill {
            background-color: #794330;
            border-radius: 8px;
        }

        .dashboard-table-scroll {
            max-height: 295px;
            overflow-y: auto;
        }

        .dashboard-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        .dashboard-table-scroll td,
        .dashboard-table-scroll th {
            vertical-align: middle;
            text-align: left;
        }
    </style>
@endsection
@push('scripts')
    @if ($forecastChart)
        {{-- Data dibuat Laravel; grafik digambar oleh Chart.js yang dibundel Vite. --}}
        <script type="application/json" id="dashboardForecastData">@json($forecastChart)</script>
        @vite('resources/js/dashboard-forecast-chart.js')
    @endif
@endpush