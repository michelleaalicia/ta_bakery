@extends('layouts.admin')

@section('title', 'Riwayat Penjualan')
@section('page-title', 'Riwayat Penjualan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}" id="salesFilterForm"
                class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label for="salesStatus" class="form-label">Status Pembayaran</label>
                    <select name="status" id="salesStatus" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach (['paid' => 'Lunas', 'pending' => 'Menunggu QRIS', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="salesFrom" class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" id="salesFrom" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label for="salesTo" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" id="salesTo" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
            </form>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-2">
                    <label for="salesPageLength" class="form-label">Tampilkan Data</label>
                    <select id="salesPageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="salesBranchFilter" class="form-label">Filter Cabang</label>
                    <select id="salesBranchFilter" class="form-select">
                        <option value="">Semua Cabang</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="salesTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Cabang</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>POS-{{ str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td data-order="{{ $sale->sale_date?->format('Y-m-d') ?? '' }}">
                                    {{ $sale->sale_date?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td>{{ $sale->branch?->name ?? 'Pusat' }}</td>
                                <td>{{ strtoupper($sale->payment_method) }}</td>
                                <td>
                                    @if ($sale->payment_status === 'paid')
                                        <span class="badge bg-success">Lunas</span>
                                    @elseif ($sale->payment_status === 'pending')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @else
                                        <span class="badge bg-secondary">Dibatalkan</span>
                                    @endif
                                </td>
                                <td data-order="{{ (float) $sale->total_amount }}" class="text-start">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-info btn-sm">Detail</a>
                                    @if ($sale->payment_status === 'paid')
                                        <a href="{{ route('sales.receipt', $sale) }}" target="_blank" rel="noopener"
                                            class="btn btn-outline-dark btn-sm">Nota PDF</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #salesTable th,
        #salesTable td {
            vertical-align: middle;
            text-align: left;
        }

        #salesTable th:first-child,
        #salesTable td:first-child {
            width: 45px !important;
            text-align: center !important;
        }

        #salesTable th:nth-child(7),
        #salesTable td:nth-child(7) {
            text-align: left !important;
        }

        #salesTable thead th.dt-orderable-asc,
        #salesTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
        }

        #salesTable thead th.dt-orderable-asc::after,
        #salesTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        #salesTable thead th.dt-ordering-asc::after {
            content: '▲';
        }

        #salesTable thead th.dt-ordering-desc::after {
            content: '▼';
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('salesFilterForm');
            if (filterForm) {
                const status = filterForm.querySelector('[name="status"]');
                const from = filterForm.querySelector('[name="from"]');
                const to = filterForm.querySelector('[name="to"]');

                function applyFilter() {
                    if (from.value && to.value && from.value > to.value) return;
                    filterForm.requestSubmit();
                }

                [status, from, to].forEach(input => input.addEventListener('change', applyFilter));
            }

            const table = new DataTable('#salesTable', {
                pageLength: 10,
                order: [[1, 'asc']],
                autoWidth: false,
                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                columnDefs: [
                    { targets: 0, orderable: false, searchable: false },
                    { targets: [2, 5, 6, 7], searchable: false },
                    { targets: 7, orderable: false }
                ],
                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada transaksi',
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

            const branchFilter = document.getElementById('salesBranchFilter');

            const branches = table.column(3).data().unique().sort().toArray();

            branches.forEach(function (branch) {
                branchFilter.add(new Option(branch, branch));
            });

            branchFilter.addEventListener('change', function () {
                table.column(3).search(this.value, { exact: true }).draw();
            });
            document.getElementById('salesPageLength').addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });
        });
    </script>
@endpush