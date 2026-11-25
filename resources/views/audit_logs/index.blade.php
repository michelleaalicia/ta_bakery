@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas')
@section('page-title', 'Riwayat Aktivitas')

@section('content')
    <div class="card mb-3">
        <div class="card-header">Filter Aktivitas</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('audit_logs.index') }}" id="auditFilterForm"
                class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="auditStartDate" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="auditStartDate" name="start_date" class="form-control"
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="auditEndDate" class="form-label">Tanggal Akhir</label>
                    <input type="date" id="auditEndDate" name="end_date" class="form-control"
                        value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="auditAction" class="form-label">Aktivitas</label>
                    <select id="auditAction" name="action" class="form-select">
                        <option value="">Semua Aktivitas</option>
                        @foreach (['created' => 'Tambah', 'updated' => 'Ubah', 'deleted' => 'Hapus'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="auditModule" class="form-label">Modul</label>
                    <select id="auditModule" name="table" class="form-select">
                        <option value="">Semua Modul</option>
                        @foreach (['sales' => 'POS', 'custom_orders' => 'Custom Order', 'production_orders' => 'Produksi', 'ingredients' => 'Bahan Baku', 'ingredient_restocks' => 'Restock Bahan', 'products' => 'Produk'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('table') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($isOwner)
                    <div class="col-md-2">
                        <label for="auditBranch" class="form-label">Cabang</label>
                        <select id="auditBranch" name="branch_id" class="form-select">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Aktivitas</div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-2">
                    <label for="auditPageLength" class="form-label">Tampilkan Data</label>
                    <select id="auditPageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="auditLogsTable" class="table table-bordered table-hover align-middle w-100">
                    <colgroup>
                        <col style="width: 5%">
                        <col style="width: 17%">
                        <col style="width: 15%">
                        <col style="width: 13%">
                        <col style="width: 13%">
                        <col style="width: 13%">
                        <col style="width: 19%">
                        <col style="width: 8%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Cabang</th>
                            <th>Modul</th>
                            <th>Aktivitas</th>
                            <th>Ringkasan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $moduleNames = [
                                    'sales' => 'POS',
                                    'custom_orders' => 'Custom Order',
                                    'production_orders' => 'Produksi',
                                    'ingredients' => 'Bahan Baku',
                                    'ingredient_restocks' => 'Restock Bahan',
                                    'products' => 'Produk',
                                ];
                                $actionNames = [
                                    'created' => 'Menambahkan',
                                    'updated' => 'Mengubah',
                                    'deleted' => 'Menghapus',
                                ];
                                $fieldNames = [
                                    'branch_id' => 'Cabang', 'sale_date' => 'Tanggal penjualan',
                                    'total_amount' => 'Total transaksi', 'payment_method' => 'Metode pembayaran',
                                    'payment_status' => 'Status pembayaran', 'order_number' => 'Nomor pesanan',
                                    'product_name' => 'Nama produk', 'quantity' => 'Jumlah',
                                    'selling_price' => 'Harga jual', 'status' => 'Status',
                                    'delivered_at' => 'Tanggal penyerahan', 'order_type' => 'Jenis produksi',
                                    'product_variant_id' => 'ID varian produk', 'hpp_per_unit' => 'HPP per unit',
                                    'name' => 'Nama', 'stock' => 'Stok', 'unit_cost' => 'Harga rata-rata per satuan',
                                    'min_stock' => 'Minimum stok', 'ingredient_id' => 'ID bahan baku',
                                    'unit_price' => 'Harga restock per satuan', 'total_cost' => 'Total biaya restock',
                                    'restock_date' => 'Tanggal restock',
                                ];
                                $moneyFields = ['total_amount', 'selling_price', 'hpp_per_unit', 'unit_cost', 'unit_price', 'total_cost'];
                                $oldValues = json_decode($log->old_values ?: '{}', true);
                                $newValues = json_decode($log->new_values ?: '{}', true);
                                $oldValues = is_array($oldValues) ? $oldValues : [];
                                $newValues = is_array($newValues) ? $newValues : [];
                                $changedFields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
                                $ingredientId = $log->table_name === 'ingredient_restocks'
                                    ? ($newValues['ingredient_id'] ?? $oldValues['ingredient_id'] ?? null)
                                    : ($log->table_name === 'ingredients' ? $log->record_id : null);
                                $ingredientInfo = $ingredientNames[(int) $ingredientId] ?? null;
                                $ingredientLabel = $ingredientInfo['name'] ?? ('Bahan #' . ($ingredientId ?? $log->record_id));
                                $ingredientUnit = $ingredientInfo['unit'] ?? '';
                                $formatAuditValue = function ($field, $value) use ($moneyFields) {
                                    if ($value === null || $value === '') return '-';
                                    if (in_array($field, $moneyFields, true) && is_numeric($value)) {
                                        return 'Rp ' . number_format((float) $value, 2, ',', '.');
                                    }
                                    if (is_bool($value)) return $value ? 'Ya' : 'Tidak';
                                    if (is_numeric($value) && !in_array($field, ['branch_id', 'product_variant_id', 'ingredient_id'], true)) {
                                        return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
                                    }
                                    return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                                };
                                $summary = match ($log->table_name) {
                                    'ingredient_restocks' => 'Restock ' . $ingredientLabel . ': +' . $formatAuditValue('quantity', $newValues['quantity'] ?? $oldValues['quantity'] ?? null) . ' ' . $ingredientUnit,
                                    'ingredients' => $ingredientLabel . ' — ' . (array_key_exists('stock', $newValues)
                                        ? 'stok ' . $formatAuditValue('stock', $oldValues['stock'] ?? null) . ' → ' . $formatAuditValue('stock', $newValues['stock']) . ' ' . $ingredientUnit
                                        : implode(', ', array_map(fn ($field) => $fieldNames[$field] ?? str_replace('_', ' ', $field), array_slice($changedFields, 0, 2)))),
                                    'sales' => 'Transaksi #' . $log->record_id . (isset($newValues['total_amount']) ? ' · ' . $formatAuditValue('total_amount', $newValues['total_amount']) : '')
                                        . (isset($newValues['payment_status']) ? ' · ' . $newValues['payment_status'] : ''),
                                    'production_orders' => 'Produksi #' . $log->record_id . (isset($newValues['quantity']) ? ' · ' . $formatAuditValue('quantity', $newValues['quantity']) . ' pcs' : ''),
                                    'custom_orders' => 'Pesanan ' . ($newValues['order_number'] ?? $oldValues['order_number'] ?? ('#' . $log->record_id)),
                                    'products' => 'Produk ' . ($newValues['name'] ?? $oldValues['name'] ?? ('#' . $log->record_id)),
                                    default => 'Data #' . $log->record_id,
                                };
                                $modalId = 'audit-detail-' . $log->id;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-nowrap"
                                    data-order="{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}">
                                    {{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>{{ $log->user?->name ?? 'Pengguna terhapus' }}</td>
                                <td>{{ $log->branch?->name ?? 'Umum' }}</td>
                                <td>{{ $moduleNames[$log->table_name] ?? $log->table_name }}</td>
                                <td>
                                    <span
                                        class="badge {{ $log->action === 'created' ? 'bg-success' : ($log->action === 'deleted' ? 'bg-danger' : 'bg-primary') }}">
                                        {{ $actionNames[$log->action] ?? ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td>{{ $summary }}</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-audit-open="{{ $modalId }}">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Modal berada di luar tabel agar tidak diubah DataTables. --}}
            @foreach ($logs as $log)
                @php
                    $oldValues = json_decode($log->old_values ?: '{}', true);
                    $newValues = json_decode($log->new_values ?: '{}', true);
                    $oldValues = is_array($oldValues) ? $oldValues : [];
                    $newValues = is_array($newValues) ? $newValues : [];
                    $fields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
                    $fieldNames = [
                        'branch_id' => 'ID Cabang', 'sale_date' => 'Tanggal Penjualan', 'total_amount' => 'Total Transaksi',
                        'payment_method' => 'Metode Pembayaran', 'payment_status' => 'Status Pembayaran',
                        'order_number' => 'Nomor Pesanan', 'product_name' => 'Nama Produk', 'quantity' => 'Jumlah',
                        'selling_price' => 'Harga Jual', 'status' => 'Status', 'delivered_at' => 'Tanggal Penyerahan',
                        'order_type' => 'Jenis Produksi', 'product_variant_id' => 'ID Varian Produk',
                        'hpp_per_unit' => 'HPP per Unit', 'name' => 'Nama', 'stock' => 'Stok',
                        'unit_cost' => 'Harga Rata-rata per Satuan', 'min_stock' => 'Minimum Stok',
                        'ingredient_id' => 'ID Bahan Baku', 'unit_price' => 'Harga Restock per Satuan',
                        'total_cost' => 'Total Biaya Restock', 'restock_date' => 'Tanggal Restock',
                    ];
                    $moneyFields = ['total_amount', 'selling_price', 'hpp_per_unit', 'unit_cost', 'unit_price', 'total_cost'];
                    $formatValue = function ($field, $value) use ($moneyFields) {
                        if ($value === null || $value === '') return '-';
                        if (in_array($field, $moneyFields, true) && is_numeric($value)) return 'Rp ' . number_format((float) $value, 2, ',', '.');
                        if (is_bool($value)) return $value ? 'Ya' : 'Tidak';
                        if (is_numeric($value) && !in_array($field, ['branch_id','ingredient_id','product_variant_id'], true))
                            return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
                        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                    };
                @endphp
                <dialog class="audit-dialog" id="audit-detail-{{ $log->id }}"
                    aria-labelledby="audit-title-{{ $log->id }}">
                    <div class="audit-dialog-panel">
                        <div class="audit-dialog-header">
                            <div>
                                <h5 class="audit-dialog-title" id="audit-title-{{ $log->id }}">Detail Aktivitas #{{ $log->id }}</h5>
                                <div class="audit-dialog-subtitle">Riwayat perubahan data pada sistem bakery</div>
                            </div>
                            <button type="button" class="audit-dialog-x" data-audit-close aria-label="Tutup">&times;</button>
                        </div>
                        <div class="audit-dialog-body">
                            <div class="audit-dialog-info-title">Informasi Aktivitas</div>
                            <div class="row g-3 audit-dialog-info">
                                    <div class="col-sm-6"><strong>Pengguna:</strong> {{ $log->user?->name ?? 'Pengguna terhapus' }}</div>
                                    <div class="col-sm-6"><strong>Waktu:</strong> {{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</div>
                                    <div class="col-sm-6"><strong>Cabang:</strong> {{ $log->branch?->name ?? 'Umum' }}</div>
                                    <div class="col-sm-6"><strong>Modul:</strong> {{ [
                                        'sales' => 'POS', 'custom_orders' => 'Custom Order', 'production_orders' => 'Produksi',
                                        'ingredients' => 'Bahan Baku', 'ingredient_restocks' => 'Restock Bahan', 'products' => 'Produk'
                                    ][$log->table_name] ?? $log->table_name }}</div>
                                    <div class="col-sm-6"><strong>Aksi:</strong> {{ [
                                        'created' => 'Menambahkan', 'updated' => 'Mengubah', 'deleted' => 'Menghapus'
                                    ][$log->action] ?? $log->action }}</div>
                                    <div class="col-sm-6"><strong>ID data:</strong> #{{ $log->record_id }}</div>
                                </div>
                                <div class="audit-dialog-info-title audit-dialog-info-title-spaced">Rincian Perubahan</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 audit-dialog-table">
                                        <thead class="table-light"><tr><th>Informasi</th><th>Nilai Lama</th><th>Nilai Baru</th></tr></thead>
                                        <tbody>
                                            @forelse ($fields as $field)
                                                <tr>
                                                    <td>{{ $fieldNames[$field] ?? str_replace('_', ' ', $field) }}</td>
                                                    <td>{{ array_key_exists($field, $oldValues) ? $formatValue($field, $oldValues[$field]) : '—' }}</td>
                                                    <td>{{ array_key_exists($field, $newValues) ? $formatValue($field, $newValues[$field]) : '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">Nilai lama/baru tidak tersedia pada log ini.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <p class="audit-dialog-note">Hanya nilai yang tercatat pada waktu aktivitas dilakukan yang ditampilkan. Tanda — berarti data tidak tersedia.</p>
                        </div>
                        <div class="audit-dialog-footer">
                            <button type="button" class="btn btn-dark btn-sm" data-audit-close>Tutup</button>
                        </div>
                    </div>
                </dialog>
            @endforeach
        </div>
    </div>

    <style>
        /* Modal mandiri: tidak tergantung pada Bootstrap JS / modal Bootstrap CSS. */
        dialog.audit-dialog {
            box-sizing: border-box;
            padding: 0 !important;
            margin: auto;
            border: none !important;
            border-radius: 12px;
            width: min(780px, calc(100vw - 32px));
            max-width: 780px;
            max-height: calc(100dvh - 36px);
            background: #fff;
            color: #332820;
            box-shadow: 0 24px 70px rgba(32, 18, 12, .30);
            overflow: hidden;
        }
        dialog.audit-dialog::backdrop { background: rgba(24, 18, 15, .56); }
        .audit-dialog-panel { display: flex; flex-direction: column; max-height: calc(100dvh - 36px); background: white; }
        .audit-dialog-header {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;
            padding: 20px 24px 18px !important;
            background: #FAF8F5; border-bottom: 1px solid #E9DFD6;
            flex-shrink: 0;
        }
        .audit-dialog-title { font-size: 19px; line-height: 1.35; font-weight: 650; color: #794330; margin: 0 0 4px; }
        .audit-dialog-subtitle { font-size: 12px; line-height: 1.5; color: #77675d; }
        .audit-dialog-x {
            border: none; background: transparent; color: #77675d; border-radius: 6px;
            font-size: 27px; line-height: 1; padding: 0 4px; cursor: pointer;
        }
        .audit-dialog-x:hover { color: #794330; background: #F3E7DA; }
        .audit-dialog-body { padding: 22px 24px 24px !important; overflow-y: auto; min-height: 0; }
        .audit-dialog-info-title { font-size: 13px; font-weight: 700; color: #794330; margin-bottom: 12px; }
        .audit-dialog-info-title-spaced { margin-top: 26px; }
        .audit-dialog-info { font-size: 13px; line-height: 1.7; color: #332820; }
        .audit-dialog-info strong { font-weight: 650; color: #65564c; }
        .audit-dialog-table { font-size: 13px; margin: 0; }
        .audit-dialog-table th { background: #F3E7DA !important; color: #332820; font-weight: 650; }
        .audit-dialog-table th, .audit-dialog-table td { padding: 11px 12px !important; vertical-align: middle; overflow-wrap: anywhere; }
        .audit-dialog-note {
            font-size: 12px; color: #77675d; line-height: 1.6;
            margin: 14px 0 0; padding: 12px 14px; border-radius: 6px;
            background: #FAF8F5; border: 1px solid #F3E7DA;
        }
        .audit-dialog-footer {
            display: flex; justify-content: flex-end; align-items: center;
            padding: 14px 24px !important; border-top: 1px solid #E9DFD6;
            background: #fff; flex-shrink: 0;
        }
        @media (max-width: 575px) {
            .audit-dialog-header { padding: 16px !important; }
            .audit-dialog-body { padding: 16px !important; }
            .audit-dialog-footer { padding: 12px 16px !important; }
        }

        /* Kolom memakai lebar tetap, sehingga tidak bergeser saat berpindah halaman. */
        #auditLogsTable {
            table-layout: fixed;
            min-width: 950px;
        }

        #auditLogsTable th,
        #auditLogsTable td {
            vertical-align: middle !important;
            text-align: left !important;
            overflow-wrap: anywhere;
        }

        #auditLogsTable th:first-child,
        #auditLogsTable td:first-child {
            text-align: center !important;
        }

        /* Sembunyikan ikon sorting bawaan supaya tidak muncul panah ganda. */
        #auditLogsTable thead th span.dt-column-order {
            display: none !important;
        }

        /* Ikon custom yang posisinya sama di setiap kolom sortable. */
        #auditLogsTable thead th.dt-orderable-asc,
        #auditLogsTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
        }

        #auditLogsTable thead th.dt-orderable-asc::after,
        #auditLogsTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #6c757d;
        }

        #auditLogsTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #212529;
        }

        #auditLogsTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #212529;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Delegasi klik agar tombol tetap bekerja setelah tabel diolah DataTables.
            document.addEventListener('click', function (event) {
                const openButton = event.target.closest('[data-audit-open]');
                if (openButton) {
                    const dialog = document.getElementById(openButton.dataset.auditOpen);
                    if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
                        dialog.showModal();
                    }
                    return;
                }

                const closeButton = event.target.closest('[data-audit-close]');
                if (closeButton) {
                    const dialog = closeButton.closest('dialog.audit-dialog');
                    if (dialog?.open) dialog.close();
                    return;
                }

                // Klik pada area gelap di luar dialog juga menutup detail.
                if (event.target.matches('dialog.audit-dialog') && event.target.open) {
                    event.target.close();
                }
            });

            const form = document.getElementById('auditFilterForm');
            if (form) {
                const from = form.querySelector('[name="start_date"]');
                const to = form.querySelector('[name="end_date"]');

                function submitFilters() {
                    if (from.value && to.value && from.value > to.value) return;
                    form.requestSubmit();
                }

                form.querySelectorAll('select, input[type="date"]').forEach(function (input) {
                    input.addEventListener('change', submitFilters);
                });
            }

            const table = new DataTable('#auditLogsTable', {
                pageLength: 10,
                order: [[1, 'desc']],
                autoWidth: false,
                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada aktivitas',
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                },
                drawCallback: function () {
                    const api = this.api();
                    const start = api.page.info().start;
                    api.column(0, { page: 'current' }).nodes().each(function (cell, index) {
                        cell.textContent = start + index + 1;
                    });
                }
            });

            document.getElementById('auditPageLength').addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });
        });
    </script>
@endpush