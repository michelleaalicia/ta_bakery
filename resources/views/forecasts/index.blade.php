@extends('layouts.admin')
@section('title', 'Forecast Penjualan')
@section('page-title', 'Forecast Penjualan')

@section('content')
<style>
.forecast-page .card { border-color:#E9DFD6; }
.forecast-page .form-label { font-weight:500; }
.forecast-page .summary-card { background:#fff; border:1px solid #E9DFD6; border-radius:8px; padding:16px; height:100%; }
.forecast-page .summary-label { color:#77675d; font-size:13px; }
.forecast-page .summary-value { color:#332820; font-weight:650; font-size:24px; margin:6px 0; }
.forecast-page .summary-note { color:#77675d; font-size:12px; }
.forecast-page .chart-shell { position:relative; width:100%; height:370px; background:#fff; }
.forecast-page .chart-shell canvas { width:100% !important; height:100% !important; }
@media(max-width:576px) { .forecast-page .chart-shell { height:290px; } }
.forecast-page details summary { cursor:pointer; font-weight:600; color:#794330; padding:8px 0; }
.forecast-page table { font-size:13px; }
.forecast-page .chart-note { font-size:12px; color:#77675d; }
</style>

<div class="forecast-page">
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('forecasts.index') }}" id="forecastForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="forecastProduct">Produk</label>
                        <select id="forecastProduct" name="product_id" class="form-select" required>
                            @foreach($productOptions as $product)
                                <option value="{{ $product->id }}" @selected($productId == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="forecastVariant">Varian</label>
                        <select id="forecastVariant" name="product_variant_id" class="form-select" required>
                            @foreach($products as $variant)
                                <option value="{{ $variant->id }}" data-product-id="{{ $variant->product_id }}" @selected($variantId == $variant->id)>{{ $variant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($isOwner && $hasBranches)
                        <div class="col-md-4">
                            <label class="form-label" for="forecastBranch">Cabang</label>
                            <select id="forecastBranch" name="branch_id" class="form-select">
                                <option value="all" @selected($branchChoice === 'all')>Semua Cabang</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($branchChoice === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label" for="forecastFrequency">Periode Peramalan</label>
                        <select id="forecastFrequency" name="frequency" class="form-select">
                            <option value="daily" @selected($frequency === 'daily')>Harian</option>
                            <option value="weekly" @selected($frequency === 'weekly')>Mingguan</option>
                            <option value="monthly" @selected($frequency === 'monthly')>Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="forecastHorizon">Prediksi ke Depan</label>
                        <select id="forecastHorizon" name="horizon" class="form-select">
                            @foreach(range(1, $maxHorizon) as $h)
                                <option value="{{ $h }}" @selected($horizon === $h)>{{ $h }} {{ $frequency === 'daily' ? 'hari' : ($frequency === 'weekly' ? 'minggu' : 'bulan') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 small text-muted" id="forecastDateRangeInfo">
                    Data historis otomatis: <strong>{{ $start->copy()->locale('id')->translatedFormat('d M Y') }} - {{ $end->copy()->locale('id')->translatedFormat('d M Y') }}</strong>
                    <span class="ms-1">({{ $frequency === 'daily' ? '60 hari' : ($frequency === 'weekly' ? '16 minggu' : '12 bulan') }} lengkap).</span>
                    Tanggal ini adalah data penjualan masa lalu, bukan tanggal hasil prediksi.
                </div>
                <details class="mt-3" @if($alphaChoice !== 'auto') open @endif>
                    <summary>Pengaturan lanjutan (opsional)</summary>
                    <div style="max-width:320px">
                        <label class="form-label" for="forecastAlpha">Alpha (α)</label>
                        <select id="forecastAlpha" name="alpha" class="form-select">
                            <option value="auto" @selected($alphaChoice === 'auto')>Otomatis — MAE terkecil</option>
                            @foreach(range(1, 9) as $digit)
                                <option value="0.{{ $digit }}" @selected($alphaChoice === '0.'.$digit)>0,{{ $digit }}</option>
                            @endforeach
                        </select>
                    </div>
                </details>
                <div class="mt-3 pt-3 border-top">
                    <button type="submit" class="btn btn-dark btn-sm">Hitung Prediksi</button>
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
        <p class="small text-muted mb-2">Evaluasi DES menggunakan {{ count($history) }} periode historis lengkap; {{ $observedDates }} periode memiliki penjualan. Hasil peramalan merupakan estimasi, bukan jaminan permintaan.</p>
        @php
            $next = $result['future'][0] ?? null;
            $periodUnit = $frequency === 'daily' ? 'hari' : ($frequency === 'weekly' ? 'minggu' : 'bulan');
        @endphp
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Prediksi {{ $periodUnit }} berikutnya</div>
                    <div class="summary-value">{{ number_format($next['units'] ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">unit</span></div>
                    <div class="summary-note">{{ $next['period_label'] ?? '-' }} · {{ $selectedVariant?->product?->name }} / {{ $selectedVariant?->name }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Alpha terpilih</div>
                    <div class="summary-value">{{ number_format($result['alpha'], 1, ',', '.') }}</div>
                    <div class="summary-note">{{ $alphaChoice === 'auto' ? 'Otomatis berdasarkan MAE' : 'Diatur pengguna' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Kesalahan peramalan</div>
                    <div class="summary-value">{{ $result['mape'] === null ? 'N/A' : number_format($result['mape'], 2, ',', '.').'%' }}</div>
                    <div class="summary-note">MAPE · MAE: {{ number_format($result['mae'], 2, ',', '.') }} unit</div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Grafik Penjualan dan Prediksi</div>
            <div class="card-body">
                <div class="chart-shell">
                    <canvas id="forecastChart" role="img" aria-label="Grafik penjualan aktual, prediksi DES historis, dan ramalan masa depan"></canvas>
                </div>
                <script type="application/json" id="forecastChartPayload">@json(['history' => $history, 'future' => $result['future']])</script>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Hasil Prediksi {{ ucfirst($periodUnit) }}</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead><tr><th>Periode</th><th class="text-end">Perhitungan DES</th><th class="text-end">Perkiraan Unit</th></tr></thead>
                        <tbody>
                            @foreach($result['future'] as $forecast)
                                <tr>
                                    <td>{{ $forecast['period_label'] ?? $forecast['period'] }}</td>
                                    <td class="text-end">{{ number_format(max(0, $forecast['raw']), 2, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($forecast['units'], 0, ',', '.') }} unit</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <details class="card mb-3">
            <summary class="px-3 py-2">Lihat rincian perhitungan Brown DES</summary>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead><tr><th>Periode</th><th class="text-end">Aktual</th><th class="text-end">S'</th><th class="text-end">S''</th><th class="text-end">Level</th><th class="text-end">Tren</th><th class="text-end">Prediksi t</th><th class="text-end">APE</th></tr></thead>
                        <tbody>
                            @foreach($history as $row)
                                <tr>
                                    <td>{{ $row['period_label'] ?? $row['period'] }}</td>
                                    <td class="text-end">{{ number_format($row['actual'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['s1'], 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['s2'], 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['level'], 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['trend'], 2, ',', '.') }}</td>
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
    const hint = document.getElementById('forecastDateHint');
    const range = document.getElementById('forecastDateRange');
    const choices = Array.from(variant.options).map(o => ({value:o.value,text:o.textContent, productId:o.dataset.productId}));
    function syncVariants(preserve) {
        const chosen = preserve ? variant.value : '';
        variant.replaceChildren();
        choices.filter(item => item.productId === product.value).forEach(item => variant.add(new Option(item.text,item.value)));
        if (chosen && Array.from(variant.options).some(o => o.value === chosen)) variant.value = chosen;
        variant.disabled = variant.options.length === 0;
    }
    const today = @json($todayDate);
    const pad = value => String(value).padStart(2, '0');
    const display = d => `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    function syncFrequency() {
        const mode = frequency.value;
        const last = new Date(today + 'T12:00:00');
        if (mode === 'daily') {
            last.setDate(last.getDate() - 1);
        } else if (mode === 'weekly') {
            const weekday = (last.getDay() + 6) % 7;
            last.setDate(last.getDate() - weekday - 1);
        } else {
            last.setDate(1);
            last.setDate(0);
        }
        const first = new Date(last);
        if (mode === 'daily') first.setDate(first.getDate() - 59);
        else if (mode === 'weekly') first.setDate(first.getDate() - 111);
        else { first.setDate(1); first.setMonth(first.getMonth() - 11); }
        if (range) range.textContent = `${display(first)} - ${display(last)}`;

        const limits = { daily: 7, weekly: 4, monthly: 3 };
        const units = { daily: 'hari', weekly: 'minggu', monthly: 'bulan' };
        const previous = Number(horizon.value) || 1;
        horizon.replaceChildren();
        for (let i = 1; i <= limits[mode]; i++) {
            horizon.add(new Option(`${i} ${units[mode]}`, String(i)));
        }
        horizon.value = String(Math.min(previous, limits[mode]));
        if (hint) hint.textContent = {
            daily: '60 hari lengkap sampai kemarin. Prediksi maksimal 7 hari.',
            weekly: '16 minggu lengkap sampai Minggu terakhir. Prediksi maksimal 4 minggu.',
            monthly: '12 bulan lengkap sampai akhir bulan lalu. Prediksi maksimal 3 bulan.',
        }[mode];
    }

    product.addEventListener('change', () => syncVariants(false));
    frequency.addEventListener('change', syncFrequency);
    syncVariants(true);
    // Periode historis hanya ditampilkan. Server menghitungnya ulang saat submit.
    // Jangan reset horizon awal di halaman (pilihan dari URL tetap dipertahankan).
    if (hint) hint.textContent = {
        daily: '60 hari lengkap sampai kemarin. Prediksi maksimal 7 hari.',
        weekly: '16 minggu lengkap sampai Minggu terakhir. Prediksi maksimal 4 minggu.',
        monthly: '12 bulan lengkap sampai akhir bulan lalu. Prediksi maksimal 3 bulan.',
    }[frequency.value];

});
</script>
@if($result)
    @vite('resources/js/forecast-chart.js')
@endif
@endsection
