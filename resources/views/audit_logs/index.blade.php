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
                        @foreach (['sales' => 'POS', 'custom_orders' => 'Custom Order', 'production_orders' => 'Produksi', 'ingredients' => 'Bahan Baku', 'products' => 'Produk'] as $key => $label)
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
                        <col style="width: 24%">
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
                                    'products' => 'Produk',
                                ];
                                $actionNames = [
                                    'created' => 'Menambahkan',
                                    'updated' => 'Mengubah',
                                    'deleted' => 'Menghapus',
                                ];
                                $changes = json_decode($log->new_values ?: $log->old_values ?: '{}', true);
                                $friendlyFields = [
                                    'payment_status' => 'Status pembayaran',
                                    'status' => 'Status',
                                    'stock' => 'Stok',
                                    'selling_price' => 'Harga jual',
                                    'quantity' => 'Jumlah',
                                    'hpp_per_unit' => 'HPP per unit',
                                    'total_amount' => 'Total transaksi',
                                    'delivered_at' => 'Tanggal penyerahan',
                                ];
                                $details = is_array($changes)
                                    ? collect($changes)->keys()
                                        ->map(fn($key) => $friendlyFields[$key] ?? str_replace('_', ' ', $key))
                                        ->take(3)->implode(', ')
                                    : '';
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
                                <td>{{ '#' . $log->record_id }}{{ $details ? ' · ' . $details : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
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