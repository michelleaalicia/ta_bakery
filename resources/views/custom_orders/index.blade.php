@extends('layouts.admin')

@section('title', 'Custom Order')
@section('page-title', 'Custom Order')

@section('content')

    <div class="card">
        <div class="card-header">
            <a href="{{ route('custom_orders.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus"></i> Tambah Custom Order
            </a>
        </div>

        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

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
                    <label for="searchOrder" class="form-label">Cari Pesanan</label>
                    <input type="text" id="searchOrder" class="form-control" placeholder="Nomor pesanan atau pelanggan...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="customOrdersTable" class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Produk Custom</th>
                            <th>Cabang</th>
                            <th>Jumlah</th>
                            <th>Tanggal Dibutuhkan</th>
                            <th>Harga Jual</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($orders as $order)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $order->order_number }}</td>
                                                <td>{{ $order->customer_name }}</td>
                                                <td>{{ $order->product_name }}</td>
                                                <td>{{ $order->branch?->name ?? '-' }}</td>
                                                <td>{{ $order->quantity }}</td>

                                                <td data-order="{{ $order->required_date }}">
                                                    {{ \Carbon\Carbon::parse($order->required_date)->format('d/m/Y') }}
                                                </td>

                                                <td>
                                                    {{ $order->selling_price !== null
                                ? 'Rp ' . number_format($order->selling_price, 0, ',', '.')
                                : 'Belum ditetapkan' }}
                                                </td>

                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ match ($order->status) {
                                'draft' => 'Draft',
                                'confirmed' => 'Dikonfirmasi',
                                'delivered' => 'Diserahkan',
                                default => ucfirst($order->status),
                            } }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <a href="{{ route('custom_orders.show', $order) }}" class="btn btn-info btn-sm">
                                                            Detail
                                                        </a>

                                                        @if ($order->status === 'draft' && auth()->user()->role?->name === 'Owner')
                                                            <a href="{{ route('custom_orders.edit', $order) }}" class="btn btn-warning btn-sm">
                                                                Edit
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <style>
        #customOrdersTable th,
        #customOrdersTable td {
            vertical-align: middle;
            text-align: left;
        }

        #customOrdersTable th:first-child,
        #customOrdersTable td:first-child {
            width: 45px !important;
            text-align: center !important;
        }

        #customOrdersTable thead th.dt-orderable-asc,
        #customOrdersTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
        }

        #customOrdersTable thead th.dt-orderable-asc::after,
        #customOrdersTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        #customOrdersTable thead th.dt-ordering-asc::after {
            content: '▲';
        }

        #customOrdersTable thead th.dt-ordering-desc::after {
            content: '▼';
        }
    </style>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = new DataTable('#customOrdersTable', {
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
                        searchable: false
                    },
                    {
                        targets: [3, 4, 5, 6, 7, 8],
                        searchable: false
                    },
                    {
                        targets: 9,
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [5, 7],
                        className: 'dt-type-numeric'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada custom order',
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

            document.getElementById('searchOrder').addEventListener('input', function () {
                table.search(this.value).draw();
            });

            document.getElementById('pageLength').addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });
        });
    </script>
@endpush