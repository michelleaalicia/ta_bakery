@extends('layouts.admin')

@section('title', 'Pembayaran Custom Order')
@section('page-title', 'Pembayaran Custom Order')

@section('content')

    <style>
        #paymentsTable thead th .dt-column-order {
            display: none !important;
        }

        #paymentsTable thead th.dt-orderable-asc,
        #paymentsTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #paymentsTable thead th.dt-orderable-asc::after,
        #paymentsTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #paymentsTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #paymentsTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label for="pageLength" class="form-label">Tampilkan Data</label>
                    <select id="pageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="searchPayment" class="form-label">Cari Pesanan</label>
                    <input type="text" id="searchPayment" class="form-control" placeholder="Nomor pesanan atau pelanggan">
                </div>
            </div>

            <div class="table-responsive">
                <table id="paymentsTable" class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Cabang</th>
                            <th>Total Tagihan</th>
                            <th>Sudah Dibayar</th>
                            <th>Sisa Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $total = (float) $order->selling_price;
                                $paid = (float) ($order->payments_sum_amount ?? 0);
                                $remaining = max(0, $total - $paid);

                                $status = $remaining <= 0 && $paid > 0
                                    ? 'Lunas'
                                    : ($paid > 0 ? 'DP Dibayar' : 'Belum Bayar');
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->branch?->name ?? '-' }}</td>
                                <td data-order="{{ $total }}">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </td>
                                <td data-order="{{ $paid }}">
                                    Rp {{ number_format($paid, 0, ',', '.') }}
                                </td>
                                <td data-order="{{ $remaining }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($status === 'Lunas')
                                        <span class="badge bg-success">Lunas</span>
                                    @elseif ($status === 'DP Dibayar')
                                        <span class="badge bg-warning text-dark">DP Dibayar</span>
                                    @else
                                        <span class="badge bg-secondary">Belum Bayar</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('custom_order_payments.show', $order) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #paymentsTable th,
        #paymentsTable td,
        #paymentsTable .dt-type-numeric {
            text-align: left !important;
            vertical-align: middle;
        }

        #paymentsTable th:first-child,
        #paymentsTable td:first-child {
            width: 45px !important;
            text-align: center !important;
        }

        #paymentsTable td:last-child {
            white-space: nowrap;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = new DataTable('#paymentsTable', {
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
                    { targets: [3, 4, 5, 6, 7, 8], searchable: false },
                    { targets: 8, orderable: false }
                ],
                language: {
                    emptyTable: 'Belum ada pesanan yang dikonfirmasi',
                    zeroRecords: 'Pesanan tidak ditemukan',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
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

            document.getElementById('searchPayment').addEventListener('input', function () {
                table.search(this.value).draw();
            });

            document.getElementById('pageLength').addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });
        });
    </script>
@endpush