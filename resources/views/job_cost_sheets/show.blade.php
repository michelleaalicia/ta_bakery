@extends('layouts.admin')

@section('title', 'Job Cost Sheet')
@section('page-title', 'Job Cost Sheet')

@section('content')
    @php
        $money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
        $phase = $isPlanned ? 'ESTIMASI' : ($isCompleted ? 'FINAL' : 'SEMENTARA');
        $bakeryName = $customOrder->tenant?->name ?? 'Bakery';
        $branchName = $customOrder->branch?->name;
        $companyLabel = $bakeryName . ($branchName ? ' - ' . $branchName : '');
        $productionLabel = $productionOrder->status === 'planned' ? 'Direncanakan' : ($isCompleted ? 'Selesai' : 'Berlangsung');
        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
        $costLines = [
            ['Biaya Bahan Baku Langsung (BBB)', $estimateMaterial, $actualMaterial],
            ['Biaya Tenaga Kerja Langsung (BTKL)', $estimateLabor, $actualLabor],
            ['Biaya Overhead Pabrik (BOP)', $estimateBop, $actualBop],
        ];
    @endphp

    <style>
        .jcs-actions { display: flex; justify-content: flex-start; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .jcs-paper { max-width: 1120px; margin: 0 auto 26px; padding: 36px 42px; background: #fff; color: #202832; border: 1px solid #d6dbe0; box-shadow: 0 2px 10px rgba(15,23,42,.04); font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.5; }
        .jcs-heading { text-align: center; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid #cdd4dc; }
        .jcs-company { font-size: 14px; font-weight: 500; letter-spacing: 0; margin-bottom: 5px; }
        .jcs-title { font-size: 20px; font-weight: 600; letter-spacing: 0; margin: 0 0 6px; }
        .jcs-subtitle { font-size: 12px; color: #687583; margin: 0; }
        .jcs-headrow { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
        .jcs-ref { font-size: 12px; font-weight: 600; }
        .jcs-status { border: 1px solid #64748b; color: #334155; padding: 4px 11px; letter-spacing: 0; font-weight: 500; font-size: 11px; }
        .jcs-meta { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #cdd4dc; margin-bottom: 19px; }
        .jcs-meta-column + .jcs-meta-column { border-left: 1px solid #cdd4dc; }
        .jcs-meta-item { display: grid; grid-template-columns: 145px 1fr; border-bottom: 1px solid #e4e8ed; min-height: 34px; }
        .jcs-meta-item:last-child { border-bottom: 0; }
        .jcs-meta-item .label { padding: 8px 10px; color: #526172; background: #f7f9fb; }
        .jcs-meta-item .value { padding: 8px 10px; font-weight: 400; overflow-wrap: anywhere; }
        .jcs-note { padding: 10px 13px; border-left: 3px solid #64748b; background: #f5f7f9; font-size: 12px; margin-bottom: 22px; }
        .jcs-section { margin-top: 23px; }
        .jcs-section-heading { font-weight: 600; font-size: 14px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 9px; letter-spacing: .015em; }
        .jcs-scroll { overflow-x: auto; }
        .jcs-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .jcs-table th, .jcs-table td { border: 1px solid #cdd4dc; padding: 9px 10px; vertical-align: middle; }
        .jcs-table thead th { background: #f4f6f8; font-weight: 600; }
        .jcs-table .number { text-align: right; white-space: nowrap; }
        .jcs-table .center { text-align: center; }
        .jcs-table tbody tr:nth-child(even) { background: #fafbfc; }
        .jcs-table .subtotal td { background: #f3f5f7; font-weight: 600; }
        .jcs-table .grand-total td { background: #e9eef2; font-weight: 600; border-top: 1px solid #aebbc6; }
        .jcs-table .muted { color: #7c8794; }
        .jcs-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 16px; }
        .jcs-tile { border: 1px solid #bfcad5; background: #f8fafc; text-align: center; padding: 17px; }
        .jcs-tile-label { color: #536375; font-size: 11px; text-transform: none; font-weight: 500; letter-spacing: 0; }
        .jcs-tile-value { font-size: 19px; font-weight: 600; margin-top: 4px; }
        .jcs-small { color: #687583; font-size: 11px; margin-top: 9px; }
        .jcs-footer { margin-top: 30px; border-top: 1px solid #cdd4dc; padding-top: 10px; font-size: 11px; color: #66768a; display: flex; justify-content: space-between; gap: 15px; }
        @media (max-width: 767px) {
            .jcs-paper { padding: 18px 13px; }
            .jcs-meta { grid-template-columns: 1fr; }
            .jcs-meta-column + .jcs-meta-column { border-left: 0; border-top: 1px solid #cdd4dc; }
            .jcs-meta-item { grid-template-columns: 125px 1fr; }
            .jcs-title { font-size: 21px; }
            .jcs-summary { grid-template-columns: 1fr; }
            .jcs-table { min-width: 660px; }
        }
        @page { size: A4 portrait; margin: 13mm; }
        @media print {
            body { background: #fff !important; }
            .main-sidebar, .sidebar, .main-header, .content-header, nav, footer, .jcs-actions { display: none !important; }
            .content-wrapper, .main-content, .container-fluid, .content, main { margin: 0 !important; padding: 0 !important; background: white !important; }
            .jcs-paper { width: 100%; max-width: none; margin: 0; border: none; box-shadow: none; padding: 0; }
            .jcs-scroll { overflow: visible; }
            .jcs-table { font-size: 10px; min-width: 0; }
            .jcs-table th, .jcs-table td { padding: 6px; }
            .jcs-section, .jcs-meta, .jcs-summary { break-inside: avoid; }
            .jcs-meta-item { min-height: 27px; }
            .jcs-meta-item .label, .jcs-meta-item .value { padding: 5px 8px; }
            .jcs-table thead th, .jcs-table .subtotal td, .jcs-table .grand-total td { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="jcs-actions">
        <a href="{{ route('production_orders.show', $productionOrder) }}" class="btn btn-outline-secondary btn-sm">Kembali ke Produksi</a>
        <a href="{{ route('custom_orders.show', $customOrder) }}" class="btn btn-outline-secondary btn-sm">Detail Custom Order</a>
        <button type="button" class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Cetak JCS</button>
    </div>

    <div class="jcs-paper">
        <header class="jcs-heading">
            <div class="jcs-company">{{ $companyLabel }}</div>
            <h1 class="jcs-title">Job Cost Sheet</h1>
        </header>

        <div class="jcs-headrow">
            <span class="jcs-ref">Nomor Job: {{ $customOrder->order_number }}</span>
            <span class="jcs-status">{{ $phase }}</span>
        </div>

        <div class="jcs-meta">
            <div class="jcs-meta-column">
                <div class="jcs-meta-item"><span class="label">Nama Pelanggan</span><span class="value">{{ $customOrder->customer_name }}</span></div>
                <div class="jcs-meta-item"><span class="label">Produk Pesanan</span><span class="value">{{ $customOrder->product_name }}</span></div>
                <div class="jcs-meta-item"><span class="label">Jumlah Unit</span><span class="value">{{ $qty($quantity) }} unit</span></div>
                <div class="jcs-meta-item"><span class="label">Tanggal Pesanan</span><span class="value">{{ $date($customOrder->order_date) }}</span></div>
            </div>
            <div class="jcs-meta-column">
                <div class="jcs-meta-item"><span class="label">No. Produksi</span><span class="value">#{{ $productionOrder->id }}</span></div>
                <div class="jcs-meta-item"><span class="label">Tanggal Produksi</span><span class="value">{{ $date($productionOrder->production_date) }}</span></div>
                <div class="jcs-meta-item"><span class="label">Batas Pesanan</span><span class="value">{{ $date($customOrder->required_date) }}</span></div>
                <div class="jcs-meta-item"><span class="label">Status Produksi</span><span class="value">{{ $productionLabel }}</span></div>
            </div>
        </div>

        <div class="jcs-note">
            @if ($isPlanned)
                Lembar estimasi. Nilai bahan, tenaga kerja, dan BOP merupakan rencana biaya.
            @elseif ($isCompleted)
                Lembar final. Biaya produksi dihitung berdasarkan realisasi tersimpan dan BOP yang dibebankan. Pesanan selesai diproduksi.
            @else
                Lembar sementara. Nilai aktual diperbarui selama produksi berlangsung dan belum menjadi biaya final.
            @endif
        </div>

        <section class="jcs-section">
            <h2 class="jcs-section-heading">01 - Ringkasan Biaya Produksi</h2>
            <div class="jcs-scroll">
                <table class="jcs-table">
                    <thead>
                        <tr><th>Komponen Biaya</th><th class="number">Estimasi</th><th class="number">{{ $isCompleted ? 'Aktual Final' : 'Aktual Sementara' }}</th><th class="number">Selisih</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($costLines as $line)
                            <tr>
                                <td>{{ $line[0] }}</td>
                                <td class="number">{{ $money($line[1]) }}</td>
                                <td class="number">{{ $isPlanned ? '-' : $money($line[2]) }}</td>
                                <td class="number">{{ $isPlanned ? '-' : $money($line[2] - $line[1]) }}</td>
                            </tr>
                        @endforeach
                        <tr class="grand-total">
                            <td>Total Biaya Produksi</td>
                            <td class="number">{{ $money($estimateTotal) }}</td>
                            <td class="number">{{ $isPlanned ? '-' : $money($actualTotal) }}</td>
                            <td class="number">{{ $isPlanned ? '-' : $money($actualTotal - $estimateTotal) }}</td>
                        </tr>
                        <tr class="subtotal">
                            <td>HPP per Unit</td>
                            <td class="number">{{ $money($estimateHpp) }}</td>
                            <td class="number">{{ $isPlanned ? '-' : $money($actualHpp) }}</td>
                            <td class="number">{{ $isPlanned ? '-' : $money($actualHpp - $estimateHpp) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="jcs-summary">
                <div class="jcs-tile"><div class="jcs-tile-label">{{ $isPlanned ? 'Total Biaya Estimasi' : ($isCompleted ? 'Total Biaya Final' : 'Total Biaya Sementara') }}</div><div class="jcs-tile-value">{{ $money($displayTotal) }}</div></div>
                <div class="jcs-tile"><div class="jcs-tile-label">{{ $isPlanned ? 'Estimasi HPP / Unit' : ($isCompleted ? 'HPP Final / Unit' : 'HPP Sementara / Unit') }}</div><div class="jcs-tile-value">{{ $money($isPlanned ? $estimateHpp : $actualHpp) }}</div></div>
            </div>
        </section>

        <section class="jcs-section">
            <h2 class="jcs-section-heading">02 - Bahan Baku Langsung (BBB)</h2>
            <div class="jcs-scroll">
                <table class="jcs-table">
                    <thead><tr><th>Nama Bahan</th><th class="center">Rencana</th><th class="center">Terpakai</th><th class="number">Harga Rencana/Satuan</th><th class="number">Harga Aktual/Satuan</th><th class="number">Biaya Rencana</th><th class="number">Biaya Aktual</th></tr></thead>
                    <tbody>
                        @forelse ($customOrder->materials as $material)
                            @php
                                $used = $productionOrder->ingredients->first(fn ($ingredient) => (int) $ingredient->ingredient_master_id === (int) $material->ingredient_master_id);
                            @endphp
                            <tr>
                                <td>{{ $material->ingredientMaster?->name ?? '-' }}</td>
                                <td class="center">{{ $qty($material->quantity) }} {{ $material->unit }}</td>
                                <td class="center">{{ $isPlanned ? '-' : ($used ? $qty($used->pivot->quantity_used) . ' ' . $used->unit : '-') }}</td>
                                <td class="number">{{ $money($material->estimated_unit_cost) }}</td>
                                <td class="number">{{ $isPlanned || !$used ? '-' : $money($used->pivot->unit_cost_used) }}</td>
                                <td class="number">{{ $money($material->estimated_total_cost) }}</td>
                                <td class="number">{{ $isPlanned || !$used ? '-' : $money((float) $used->pivot->quantity_used * (float) $used->pivot->unit_cost_used) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="center muted">Belum ada bahan baku.</td></tr>
                        @endforelse
                        <tr class="subtotal"><td colspan="5">Total BBB</td><td class="number">{{ $money($estimateMaterial) }}</td><td class="number">{{ $isPlanned ? '-' : $money($actualMaterial) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="jcs-section">
            <h2 class="jcs-section-heading">03 - Tenaga Kerja Langsung (BTKL)</h2>
            <div class="jcs-scroll">
                <table class="jcs-table">
                    <thead><tr><th>Nama Karyawan</th><th>Pekerjaan</th><th class="center">Jam Rencana</th><th class="center">Jam Aktual</th><th class="number">Tarif Rencana/Jam</th><th class="number">Tarif Aktual/Jam</th><th class="number">Biaya Rencana</th><th class="number">Biaya Aktual</th></tr></thead>
                    <tbody>
                        @forelse ($customOrder->labors as $labor)
                            @php
                                $worked = $productionOrder->users->firstWhere('id', $labor->user_id);
                            @endphp
                            <tr>
                                <td>{{ $labor->user?->name ?? '-' }}</td>
                                <td>{{ $labor->job_description }}</td>
                                <td class="center">{{ $qty($labor->estimated_hours) }} jam</td>
                                <td class="center">{{ $isPlanned ? '-' : ($worked ? $qty($worked->pivot->labor_hours) . ' jam' : '-') }}</td>
                                <td class="number">{{ $money($labor->estimated_rate) }}</td>
                                <td class="number">{{ $isPlanned || !$worked ? '-' : $money($worked->pivot->labor_rate_per_hour ?? 0) }}</td>
                                <td class="number">{{ $money($labor->estimated_cost) }}</td>
                                <td class="number">{{ $isPlanned || !$worked ? '-' : $money((float) $worked->pivot->labor_hours * (float) ($worked->pivot->labor_rate_per_hour ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="center muted">Belum ada tenaga kerja.</td></tr>
                        @endforelse
                        <tr class="subtotal"><td colspan="6">Total BTKL</td><td class="number">{{ $money($estimateLabor) }}</td><td class="number">{{ $isPlanned ? '-' : $money($actualLabor) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="jcs-section">
            <h2 class="jcs-section-heading">04 - Biaya Overhead Pabrik (BOP)</h2>
            <div class="jcs-scroll"><table class="jcs-table">
                <thead><tr><th>Dasar Pembebanan</th><th class="number">BOP Estimasi</th><th class="number">BOP Dibebankan</th></tr></thead>
                <tbody><tr><td>Nominal BOP per pesanan yang ditetapkan sebelum produksi</td><td class="number">{{ $money($estimateBop) }}</td><td class="number">{{ $isPlanned ? '-' : $money($actualBop) }}</td></tr></tbody>
            </table></div>
        </section>

        <section class="jcs-section">
            <h2 class="jcs-section-heading">05 - Harga Penawaran dan Laba Kotor</h2>
            <div class="jcs-scroll"><table class="jcs-table">
                <tbody>
                    <tr><td>Harga Penawaran Total Pesanan</td><td class="number">{{ $sellingPrice === null ? '-' : $money($sellingPrice) }}</td></tr>
                    <tr><td>{{ $isPlanned ? 'Estimasi Total Biaya Produksi' : ($isCompleted ? 'Total Biaya Produksi Final' : 'Total Biaya Produksi Sementara') }}</td><td class="number">{{ $money($displayTotal) }}</td></tr>
                    <tr class="subtotal"><td>{{ $isCompleted ? 'Laba Kotor Final' : 'Perkiraan Laba Kotor' }}</td><td class="number">{{ $grossProfit === null ? '-' : $money($grossProfit) }}</td></tr>
                    <tr><td>Margin Laba Kotor</td><td class="number">{{ $grossMargin === null ? '-' : number_format($grossMargin, 2, ',', '.') . '%' }}</td></tr>
                </tbody>
            </table></div>
        </section>

        <div class="jcs-footer">
            <span>{{ $companyLabel }} · {{ $customOrder->order_number }}</span>
            <span>Job Cost Sheet · {{ $phase }}</span>
        </div>
    </div>
@endsection
