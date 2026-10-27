@extends('layouts.admin')

@section('title', 'Laporan Laba Kotor')
@section('page-title', 'Laporan Laba Kotor')

@section('content')
    <div class="card mb-3">
        <div class="card-header">Filter Periode</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form id="grossProfitFilterForm" action="{{ route('reports.gross-profit') }}" method="GET"
                class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="grossStartDate" class="form-label">Tanggal Mulai</label>
                    <input id="grossStartDate" class="form-control" type="date" name="start_date"
                        value="{{ $startDate }}" required>
                </div>
                <div class="col-md-3">
                    <label for="grossEndDate" class="form-label">Tanggal Akhir</label>
                    <input id="grossEndDate" class="form-control" type="date" name="end_date"
                        value="{{ $endDate }}" required>
                </div>
                @if ($isOwner)
                    <div class="col-md-2">
                        <label for="grossBranch" class="form-label">Cabang</label>
                        <select id="grossBranch" name="branch_id" class="form-select">
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
                    <label for="grossSource" class="form-label">Sumber</label>
                    <select id="grossSource" class="form-select" name="source">
                        <option value="all" @selected($source === 'all')>Semua</option>
                        <option value="pos" @selected($source === 'pos')>POS</option>
                        <option value="custom" @selected($source === 'custom')>Custom Order</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if ($missingCount > 0)
        <div class="alert alert-warning">
            <strong>{{ $missingCount }} transaksi belum memiliki HPP lengkap.</strong>
            Total laba kotor belum ditampilkan agar tidak menyesatkan. Periksa HPP historis transaksi tersebut.
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted mb-1">Total Penjualan</div>
                <h4 class="mb-0 fw-normal">Rp {{ number_format($revenueTotal, 0, ',', '.') }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted mb-1">HPP {{ $missingCount ? '(yang diketahui)' : '' }}</div>
                <h4 class="mb-0 fw-normal">Rp {{ number_format($costKnownTotal, 0, ',', '.') }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted mb-1">Laba Kotor</div>
                <h4 class="mb-0 fw-normal">{{ $grossTotal === null ? 'Belum dapat dihitung' : 'Rp ' . number_format($grossTotal, 0, ',', '.') }}</h4>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Rincian Laba Kotor</div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-2">
                    <label for="grossPageLength" class="form-label">Tampilkan Data</label>
                    <select id="grossPageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="grossProfitTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light"><tr>
                        <th>No</th><th>Tanggal</th><th>Sumber</th><th>Referensi</th><th>Cabang</th>
                        <th>Penjualan</th><th>HPP</th><th>Laba Kotor</th><th>Detail</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td data-order="{{ $row['date'] }}">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td><span class="badge {{ $row['source'] === 'POS' ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $row['source'] }}</span></td>
                                <td>{{ $row['reference'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td data-order="{{ $row['revenue'] }}">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                <td data-order="{{ $row['cost'] ?? -1 }}">{{ $row['cost'] === null ? 'Belum tersedia' : 'Rp ' . number_format($row['cost'], 0, ',', '.') }}</td>
                                <td data-order="{{ $row['gross'] ?? -1 }}">{{ $row['gross'] === null ? '-' : 'Rp ' . number_format($row['gross'], 0, ',', '.') }}</td>
                                <td><a href="{{ $row['url'] }}" class="btn btn-sm btn-outline-secondary">Lihat</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light"><tr>
                        <th colspan="5" class="text-end">Total</th>
                        <th>Rp {{ number_format($revenueTotal, 0, ',', '.') }}</th>
                        <th>{{ $missingCount ? 'Belum lengkap' : 'Rp ' . number_format($costKnownTotal, 0, ',', '.') }}</th>
                        <th>{{ $grossTotal === null ? 'Belum lengkap' : 'Rp ' . number_format($grossTotal, 0, ',', '.') }}</th>
                        <th></th>
                    </tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <style>
        #grossProfitTable th, #grossProfitTable td { vertical-align: middle; text-align: left !important; }
        #grossProfitTable th:first-child, #grossProfitTable td:first-child { width: 45px !important; text-align: center !important; }
        #grossProfitTable tfoot th:first-child { text-align: right !important; }
        #grossProfitTable thead th.dt-orderable-asc, #grossProfitTable thead th.dt-orderable-desc {
            position: relative; padding-right: 28px !important;
        }
        #grossProfitTable thead th.dt-orderable-asc::after, #grossProfitTable thead th.dt-orderable-desc::after {
            content: '↕'; position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        }
        #grossProfitTable thead th.dt-ordering-asc::after { content: '▲'; }
        #grossProfitTable thead th.dt-ordering-desc::after { content: '▼'; }
    </style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('grossProfitFilterForm');
        if (filterForm) {
            const start = filterForm.querySelector('[name="start_date"]');
            const end = filterForm.querySelector('[name="end_date"]');
            const branch = filterForm.querySelector('[name="branch_id"]');
            const source = filterForm.querySelector('[name="source"]');
            const applyFilter = function () {
                if (!start.value || !end.value || start.value > end.value) return;
                filterForm.requestSubmit();
            };
            [start, end, branch, source].forEach(el => el?.addEventListener('change', applyFilter));
        }

        const table = new DataTable('#grossProfitTable', {
            pageLength: 10,
            order: [[1, 'desc']],
            autoWidth: false,
            layout: { topStart: null, topEnd: null, bottomStart: 'info', bottomEnd: 'paging' },
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                { targets: 8, orderable: false, searchable: false }
            ],
            language: {
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data', infoFiltered: '',
                zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada transaksi',
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
        document.getElementById('grossPageLength').addEventListener('change', function () {
            table.page.len(Number(this.value)).draw();
        });
    });
</script>
@endpush
