@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form action="{{ route('reports.sales') }}" method="GET" id="salesReportFilterForm"
                class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}"
                        required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                </div>
                @if ($isOwner)
                    <div class="col-md-2">
                        <label for="branch_id" class="form-label">Cabang</label>
                        <select id="branch_id" name="branch_id" class="form-select">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label for="source" class="form-label">Sumber</label>
                    <select id="source" name="source" class="form-select">
                        <option value="all" @selected($source === 'all')>Semua</option>
                        <option value="pos" @selected($source === 'pos')>POS</option>
                        <option value="custom" @selected($source === 'custom')>Custom Order</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Penjualan POS</div>
                    <h4 class="mb-1">Rp {{ number_format($posTotal, 0, ',', '.') }}</h4>
                    <small class="text-muted">{{ $posCount }} transaksi lunas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Penjualan Custom</div>
                    <h4 class="mb-1">Rp {{ number_format($customTotal, 0, ',', '.') }}</h4>
                    <small class="text-muted">{{ $customCount }} pesanan diserahkan</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Total Penjualan</div>
                    <h4 class="mb-1">Rp {{ number_format($grandTotal, 0, ',', '.') }}</h4>
                    <small class="text-muted">{{ $posCount + $customCount }} transaksi</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-2">
                    <label for="salesReportPageLength" class="form-label">Tampilkan Data</label>
                    <select id="salesReportPageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="salesReportTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Sumber</th>
                            <th>Referensi</th>
                            <th>Deskripsi</th>
                            <th>Cabang</th>
                            <th>Penjualan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td data-order="{{ $row['date'] }}">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                                </td>
                                <td>
                                    <span class="badge {{ $row['type'] === 'POS' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td>{{ $row['reference'] }}</td>
                                <td>{{ $row['description'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td data-order="{{ $row['amount'] }}">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                <td>
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ $row['url'] }}">Lihat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Total</th>
                            <th>Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <style>
        #salesReportTable th,
        #salesReportTable td {
            vertical-align: middle;
            text-align: left;
        }

        #salesReportTable thead th:first-child,
        #salesReportTable tbody td:first-child {
            width: 45px !important;
            text-align: center !important;
        }

        #salesReportTable thead th.dt-orderable-asc,
        #salesReportTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
        }

        #salesReportTable thead th.dt-orderable-asc::after,
        #salesReportTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        #salesReportTable thead th.dt-ordering-asc::after {
            content: '▲';
        }

        #salesReportTable thead th.dt-ordering-desc::after {
            content: '▼';
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('salesReportFilterForm');

            if (filterForm) {
                const startDate = filterForm.querySelector('[name="start_date"]');
                const endDate = filterForm.querySelector('[name="end_date"]');
                const branch = filterForm.querySelector('[name="branch_id"]');
                const source = filterForm.querySelector('[name="source"]');

                function applyReportFilter() {
                    if (!startDate.value || !endDate.value) return;
                    if (startDate.value > endDate.value) return;

                    filterForm.requestSubmit();
                }

                [startDate, endDate, branch, source].forEach(function (input) {
                    input?.addEventListener('change', applyReportFilter);
                });
            }

            const table = new DataTable('#salesReportTable', {
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
                    { targets: 0, orderable: false, searchable: false },
                    { targets: 7, orderable: false, searchable: false }
                ],
                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Tidak ada transaksi penjualan pada filter ini',
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

            document.getElementById('salesReportPageLength').addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });
        });
    </script>
@endpush