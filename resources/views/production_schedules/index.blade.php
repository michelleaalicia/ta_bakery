@extends('layouts.admin')

@section('title', 'Jadwal Produksi')
@section('page-title', 'Jadwal Produksi')

@section('content')

    <style>
        #scheduleTable th,
        #scheduleTable td {
            vertical-align: middle;
        }

        #scheduleTable th:first-child,
        #scheduleTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #scheduleTable .dt-type-numeric {
            text-align: left !important;
        }

        #scheduleTable thead th .dt-column-order {
            display: none !important;
        }

        #scheduleTable thead th.dt-orderable-asc,
        #scheduleTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #scheduleTable thead th.dt-orderable-asc::after,
        #scheduleTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #scheduleTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #scheduleTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-body">

            <form method="GET" action="{{ route('production_schedules.index') }}" id="filterSchedule"
                class="row g-3 align-items-end mb-3">

                <div class="col-md-3">
                    <label for="filterDate" class="form-label">Tanggal Produksi</label>

                    <input type="date" name="date" id="filterDate" class="form-control" value="{{ $date }}">
                </div>

                @if ($isOwner && $branches->isNotEmpty())
                    <div class="col-md-3">
                        <label for="filterBranch" class="form-label">Cabang</label>

                        <select name="branch_id" id="filterBranch" class="form-select">
                            <option value="">Semua Cabang</option>

                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

            </form>

            <div class="mb-3">
                <h6 class="fw-semibold mb-1">
                    Jadwal {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                </h6>

                <small class="text-muted">
                    {{ $isOwner
        ? 'Daftar penugasan karyawan produksi.'
        : 'Daftar pekerjaan yang ditugaskan kepada Anda.' }}
                </small>
            </div>

            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-2">
                    <label for="pageLength" class="form-label">Tampilkan Data</label>

                    <select id="pageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="searchSchedule" class="form-label">Cari Jadwal</label>

                    <input type="text" id="searchSchedule" class="form-control" placeholder="Cari produk atau varian...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="scheduleTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Cabang</th>
                            <th>Jumlah</th>

                            @if ($isOwner)
                                <th>Karyawan</th>
                            @endif

                            <th>Pekerjaan</th>
                            <th>Jam Kerja</th>
                            <th>Status Produksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $no = 1; @endphp

                        @foreach ($schedules as $schedule)
                            @foreach ($schedule->users as $employee)
                                <tr>
                                    <td>{{ $no++ }}</td>

                                    <td>{{ $schedule->order_type === 'custom' ? ($schedule->customOrder?->product_name ?? 'Produk Custom') : ($schedule->productVariant?->product?->name ?? '-') }}
                                    </td>

                                    <td>{{ $schedule->order_type === 'custom' ? 'Custom' : ($schedule->productVariant?->name ?? '-') }}
                                    </td>

                                    <td>{{ $schedule->branch?->name ?? 'Bakery Utama' }}</td>

                                    <td data-order="{{ $schedule->quantity }}">
                                        {{ number_format($schedule->quantity, 0, ',', '.') }}
                                    </td>

                                    @if ($isOwner)
                                        <td>{{ $employee->name }}</td>
                                    @endif

                                    <td>{{ $employee->pivot->job_description }}</td>

                                    <td data-order="{{ $employee->pivot->labor_hours }}">
                                        {{ rtrim(rtrim(number_format((float) $employee->pivot->labor_hours, 2, ',', '.'), '0'), ',') }}
                                        jam
                                    </td>

                                    <td>
                                        @if ($schedule->status === 'planned')
                                            <span class="badge bg-secondary">Direncanakan</span>
                                        @elseif ($schedule->status === 'in_progress')
                                            <span class="badge bg-warning text-dark">Berlangsung</span>
                                        @elseif ($schedule->status === 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const form = document.getElementById('filterSchedule');
            const dateInput = document.getElementById('filterDate');
            const branchInput = document.getElementById('filterBranch');

            dateInput.addEventListener('change', function () {
                form.submit();
            });

            if (branchInput) {
                branchInput.addEventListener('change', function () {
                    form.submit();
                });
            }

            const isOwner = @json($isOwner);
            const statusColumn = isOwner ? 8 : 7;

            const table = new DataTable('#scheduleTable', {
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
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: [3, 4, 6, 7, statusColumn].filter(index => index !== statusColumn),
                        searchable: false
                    },
                    {
                        targets: statusColumn,
                        searchable: false
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Tidak ada jadwal produksi pada tanggal ini.',
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

                    api.column(0, { page: 'current' }).nodes()
                        .each(function (cell, index) {
                            cell.textContent = start + index + 1;
                        });
                }
            });

            document.getElementById('searchSchedule')
                .addEventListener('input', function () {
                    table.search(this.value).draw();
                });

            document.getElementById('pageLength')
                .addEventListener('change', function () {
                    table.page.len(Number(this.value)).draw();
                });

        });
    </script>
@endpush