@extends('layouts.admin')

@section('title', 'Produksi')
@section('page-title', 'Produksi')

@section('content')

    <style>
        #productionTable th,
        #productionTable td {
            vertical-align: middle;
        }

        #productionTable th:first-child,
        #productionTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #productionTable .dt-type-numeric,
        #productionTable .dt-type-date {
            text-align: left !important;
        }

        #productionTable td:last-child {
            white-space: nowrap;
        }

        #productionTable thead th .dt-column-order {
            display: none !important;
        }

        #productionTable thead th.dt-orderable-asc,
        #productionTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #productionTable thead th.dt-orderable-asc::after,
        #productionTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #productionTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #productionTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('production_orders.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Produksi
            </a>
        </div>

        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-2">
                    <label for="filterBranch" class="form-label">Cabang</label>

                    <select id="filterBranch" class="form-select">
                        <option value="">Semua Cabang</option>

                        @foreach ($productionOrders->pluck('branch.name')->filter()->unique()->sort() as $branch)
                            <option value="{{ $branch }}">
                                {{ $branch }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">Status Produksi</label>

                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Direncanakan">Direncanakan</option>
                        <option value="Berlangsung">Berlangsung</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>

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
                    <label for="searchProduction" class="form-label">Cari Produksi</label>

                    <input type="text" id="searchProduction" class="form-control" placeholder="Cari produk atau varian...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="productionTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Cabang</th>
                            <th>Jumlah</th>
                            <th>Tanggal Produksi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($productionOrders as $productionOrder)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    @if ($productionOrder->order_type === 'custom')
                                        {{ $productionOrder->customOrder?->product_name ?? 'Produk Custom' }}
                                    @else
                                        {{ $productionOrder->productVariant?->product?->name ?? '-' }}
                                    @endif
                                </td>

                                <td>
                                    @if ($productionOrder->order_type === 'custom')
                                        Custom
                                    @else
                                        {{ $productionOrder->productVariant?->name ?? '-' }}
                                    @endif
                                </td>

                                <td>{{ $productionOrder->branch?->name ?? 'Bakery Utama' }}</td>

                                <td data-order="{{ $productionOrder->quantity }}">
                                    {{ rtrim(rtrim(number_format($productionOrder->quantity, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td
                                    data-order="{{ \Carbon\Carbon::parse($productionOrder->production_date)->format('Y-m-d') }}">
                                    {{ \Carbon\Carbon::parse($productionOrder->production_date)->format('d/m/Y') }}
                                </td>

                                <td>
                                    @if ($productionOrder->status === 'planned')
                                        <span class="badge bg-secondary">Direncanakan</span>
                                    @elseif ($productionOrder->status === 'in_progress')
                                        <span class="badge bg-warning text-dark">Berlangsung</span>
                                    @elseif ($productionOrder->status === 'completed')
                                        <span class="badge bg-success">Selesai</span>
                                    @else
                                        <span class="badge bg-danger">Dibatalkan</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('production_orders.show', $productionOrder) }}"
                                        class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    @if ($productionOrder->status === 'planned' && $productionOrder->order_type === 'regular')
                                        <a href="{{ route('production_orders.edit', $productionOrder) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('production_orders.destroy', $productionOrder) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produksi ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
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

            const table = new DataTable('#productionTable', {
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
                        targets: [0, 7],
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [3, 4, 5, 6],
                        searchable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 4,
                        width: '100px'
                    },
                    {
                        targets: 5,
                        width: '150px'
                    },
                    {
                        targets: 7,
                        width: '180px'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data produksi',
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

            const searchInput = document.getElementById('searchProduction');
            const branchInput = document.getElementById('filterBranch');
            const statusInput = document.getElementById('filterStatus');
            const pageLengthInput = document.getElementById('pageLength');

            function filterProductions() {
                const keyword = searchInput.value;
                const branch = branchInput.value;
                const status = statusInput.value;

                table.search(keyword);

                table.column(3).search(function (value) {
                    return branch === '' || value.trim() === branch;
                });

                table.column(6).search(function (value) {
                    return status === '' || value.trim() === status;
                });

                table.draw();
            }

            searchInput.addEventListener('input', filterProductions);
            branchInput.addEventListener('change', filterProductions);
            statusInput.addEventListener('change', filterProductions);

            pageLengthInput.addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });

            filterProductions();

        });
    </script>
@endpush