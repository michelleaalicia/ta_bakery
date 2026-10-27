@extends('layouts.admin')
@section('title', 'Prediksi Penjualan')
@section('page-title', 'Prediksi Penjualan')

@section('content')
<style>
.forecast-page .card { border: 1px solid #dee2e6; border-radius: 8px; }
.forecast-page .card-header { background: #fff; border-bottom: 1px solid #e9ecef; padding: 14px 18px; }
.forecast-page .card-body { padding: 18px; }
.forecast-page .form-label { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.forecast-page .form-text, .forecast-page .help-text { color: #6c757d; font-size: 12px; }
.forecast-page .metric { border: 1px solid #e2e6e4; border-radius: 8px; background: #fff; padding: 17px; height: 100%; }
.forecast-page .metric-label { color: #66736c; font-size: 12px; margin-bottom: 7px; }
.forecast-page .metric-value { color: #243b35; font-size: 24px; font-weight: 650; line-height: 1.2; }
.forecast-page .metric-note { font-size: 12px; color: #6c757d; margin-top: 7px; }
.forecast-page .btn-forecast { background: #345b4c; color: #fff; border-color: #345b4c; }
.forecast-page .btn-forecast:hover { background: #284739; color: #fff; border-color: #284739; }
.forecast-page .section-note { color: #6c757d; font-size: 13px; }
.forecast-page .details-panel summary { font-size: 14px; font-weight: 600; cursor: pointer; padding: 16px 18px; list-style: none; }
.forecast-page .details-panel summary::-webkit-details-marker { display: none; }
.forecast-page .details-panel summary::after { content: '+'; float: right; color: #486a60; font-size: 19px; line-height: 1; }
.forecast-page .details-panel[open] summary::after { content: '−'; }
.forecast-page .table thead th { font-size: 12px; font-weight: 600; background: #f6f8f7; }
</style>
<div class="forecast-page">

    <div class="card mb-3">
        <div class="card-header"><strong>Atur Prediksi</strong></div>
        <div class="card-body">
            <form method="GET" action="{{ route('forecasts.index') }}" id="forecastForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="forecastProduct">1. Produk</label>
                        <select id="forecastProduct" name="product_id" class="form-select" required>
                            @foreach($productOptions as $product)
                                <option value="{{ $product->id }}" @selected($productId == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="forecastVariant">2. Varian</label>
                        <select id="forecastVariant" name="product_variant_id" class="form-select" required>
                            @foreach($products as $variant)
                                <option value="{{ $variant->id }}" data-product-id="{{ $variant->product_id }}" @selected($variantId == $variant->id)>{{ $variant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($isOwner && $hasBranches)
                        <div class="col-md-4">
                            <label class="form-label" for="forecastBranch">3. Cabang</label>
                            <select id="forecastBranch" name="branch_id" class="form-select" required>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label" for="forecastFrequency">Jenis Prediksi</label>
                        <select id="forecastFrequency" name="frequency" class="form-select">
                            <option value="daily" @selected($frequency === 'daily')>Harian</option>
                            <option value="monthly" @selected($frequency === 'monthly')>Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="forecastStart">Dari Tanggal</label>
                        <input id="forecastStart" type="date" name="start_date" value="{{ $start->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="forecastEnd">Sampai Tanggal</label>
                        <input id="forecastEnd" type="date" name="end_date" value="{{ $end->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="forecastHorizon">Prediksi ke Depan</label>
                        <select id="forecastHorizon" name="horizon" class="form-select">
                            @foreach(range(1,7) as $h)
                                <option value="{{ $h }}" @selected($horizon === $h)>{{ $h }} {{ $frequency === 'daily' ? 'hari' : 'bulan' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <details class="mt-3" id="forecastAdvanced" @if($alphaChoice !== 'auto') open @endif>
                    <summary class="small" style="cursor:pointer;color:#345b4c;font-weight:600">Pengaturan lanjutan (opsional)</summary>
                    <div class="mt-2" style="max-width:340px">
                        <label for="forecastAlpha" class="form-label">Parameter Alpha (α)</label>
                        <select id="forecastAlpha" name="alpha" class="form-select">
                            <option value="auto" @selected($alphaChoice === 'auto')>Otomatis (disarankan)</option>
                            @foreach(range(1,9) as $n)
                                <option value="{{ '0.'.$n }}" @selected($alphaChoice === '0.'.$n)>{{ '0,'.$n }}</option>
                            @endforeach
                        </select>
                    </div>
                </details>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top">
                    <button type="submit" class="btn btn-forecast px-4">Hitung Prediksi</button>
                </div>
            </form>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if($message)
        <div class="alert alert-warning">{{ $message }}</div>
    @endif

    @if($result)
        @php
            $firstForecast = $result['future'][0] ?? null;
            $totalForecast = array_sum(array_column($result['future'], 'units'));
            $periodName = $frequency === 'daily' ? 'hari' : 'bulan';
        @endphp
        <div class="mb-2"><h5 class="mb-1">Ringkasan Prediksi</h5><div class="section-note">{{ $selectedVariant?->product?->name }} - {{ $selectedVariant?->name }}</div></div>
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Prediksi Periode Berikutnya</div><div class="metric-value">{{ number_format($firstForecast['units'] ?? 0, 0, ',', '.') }} <small class="fs-6 fw-normal">unit</small></div><div class="metric-note">{{ $firstForecast['period'] ?? '-' }}</div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Total Prediksi {{ $horizon }} {{ $periodName }}</div><div class="metric-value">{{ number_format($totalForecast, 0, ',', '.') }} <small class="fs-6 fw-normal">unit</small></div><div class="metric-note">Jumlah rekomendasi seluruh periode</div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Data Historis</div><div class="metric-value">{{ count($history) }} <small class="fs-6 fw-normal">{{ $periodName }}</small></div><div class="metric-note">{{ $observedDates }} periode ada penjualan</div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Rata-rata Kesalahan (MAPE)</div><div class="metric-value">{{ $result['mape'] === null ? 'N/A' : number_format($result['mape'], 2, ',', '.').'%' }}</div><div class="metric-note">{{ $result['mape'] === null ? 'Tidak dapat dihitung dari data aktual nol' : $result['mape_count'].' periode aktual > 0' }}</div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Hasil Prediksi {{ $frequency === 'daily' ? 'Harian' : 'Bulanan' }}</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-2">
                        <thead><tr><th>{{ $frequency === 'daily' ? 'Tanggal' : 'Bulan' }}</th><th class="text-end">Perkiraan Penjualan</th><th class="text-end">Saran Jumlah Unit</th></tr></thead>
                        <tbody>
                            @foreach($result['future'] as $forecast)
                                <tr>
                                    <td>{{ $forecast['period'] }}</td>
                                    <td class="text-end">{{ number_format(max(0, $forecast['raw']), 2, ',', '.') }} unit</td>
                                    <td class="text-end fw-semibold">{{ number_format($forecast['units'], 0, ',', '.') }} unit</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <details class="card details-panel mb-3">
            <summary>Detail metode & perhitungan DES <span class="fw-normal text-muted small ms-2"></span></summary>
            <div class="card-body border-top">
                               <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm align-middle small">
                        <thead><tr><th>t</th><th>Periode</th><th class="text-end">Xt</th><th class="text-end">S't</th><th class="text-end">S''t</th><th class="text-end">at</th><th class="text-end">bt</th><th class="text-end">Ft</th><th class="text-end">APE</th></tr></thead>
                        <tbody>
                            @foreach($history as $row)
                                <tr>
                                    <td>{{ $row['t'] }}</td><td>{{ $row['period'] }}</td>
                                    <td class="text-end">{{ number_format($row['actual'], 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['s1'], 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['s2'], 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['level'], 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['trend'], 3, ',', '.') }}</td>
                                    <td class="text-end">{{ $row['forecast'] === null ? '-' : number_format($row['forecast'], 2, ',', '.') }}</td>
                                    <td class="text-end">{{ $row['ape'] === null ? '-' : number_format($row['ape'], 2, ',', '.').'%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const product = document.getElementById('forecastProduct');
    const variant = document.getElementById('forecastVariant');
    const frequency = document.getElementById('forecastFrequency');
    const horizon = document.getElementById('forecastHorizon');
    if (!product || !variant) return;
    const choices = Array.from(variant.options).map(o => ({
        value: o.value, text: o.textContent, productId: o.dataset.productId
    }));
    function syncVariants(preserve) {
        const oldId = preserve ? variant.value : '';
        variant.innerHTML = '';
        choices.filter(item => item.productId === product.value).forEach(item => {
            const option = new Option(item.text, item.value);
            variant.add(option);
        });
        if (oldId && Array.from(variant.options).some(o => o.value === oldId)) {
            variant.value = oldId;
        }
        variant.disabled = variant.options.length === 0;
    }
    function syncHorizon() {
        Array.from(horizon.options).forEach(o => {
            o.textContent = o.value + (frequency.value === 'daily' ? ' hari' : ' bulan');
        });
    }
    product.addEventListener('change', () => syncVariants(false));
    frequency.addEventListener('change', syncHorizon);
    syncVariants(true);
    syncHorizon();
});
</script>
@endsection
