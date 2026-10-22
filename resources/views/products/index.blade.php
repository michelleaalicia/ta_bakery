@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')

@section('content')

    <style>
        #productsTable th,
        #productsTable td {
            vertical-align: middle;
        }

        #productsTable th:first-child,
        #productsTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #productsTable th:nth-child(4),
        #productsTable td:nth-child(4) {
            text-align: center !important;
        }

        #productsTable td:last-child {
            white-space: nowrap;
        }

        #productsTable thead th .dt-column-order {
            display: none !important;
        }

        #productsTable thead th.dt-orderable-asc,
        #productsTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #productsTable thead th.dt-orderable-asc::after,
        #productsTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #productsTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #productsTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('products.create') }}" class="btn btn-dark btn-sm">
                Tambah Produk
            </a>
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-2">
                    <label for="filterCategory" class="form-label">
                        Kategori
                    </label>

                    <select id="filterCategory" class="form-select">
                        <option value="">Semua Kategori</option>

                        @foreach ($products->pluck('category.name')->filter()->unique()->sort() as $category)
                            <option value="{{ $category }}">
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">
                        Status
                    </label>

                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="pageLength" class="form-label">
                        Tampilkan Data
                    </label>

                    <select id="pageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="searchProduct" class="form-label">
                        Cari Produk
                    </label>

                    <input type="text" id="searchProduct" class="form-control" placeholder="Cari nama produk...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="productsTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Jumlah Varian</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $product->name }}</td>

                                <td>{{ $product->category?->name ?? '-' }}</td>

                                <td>{{ $product->variants_count }}</td>

                                <td>{{ $product->description ?? '-' }}</td>

                                <td>
                                    @if ($product->status === 'active')
                                        <span class="badge text-bg-success">Aktif</span>
                                    @else
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus produk ini?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
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

            const table = new DataTable('#productsTable', {
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
                        targets: [0, 6],
                        orderable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 3,
                        width: '120px',
                        className: 'text-center'
                    },
                    {
                        targets: 6,
                        width: '210px',
                        searchable: false
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada produk',
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

            const searchInput = document.getElementById('searchProduct');
            const categoryInput = document.getElementById('filterCategory');
            const statusInput = document.getElementById('filterStatus');
            const pageLengthInput = document.getElementById('pageLength');

            function filterProducts() {
                const keyword = searchInput.value;
                const category = categoryInput.value;
                const status = statusInput.value;

                table.column(1).search(keyword);

                table.column(2).search(function (value) {
                    return category === '' || value.trim() === category;
                });

                table.column(5).search(function (value) {
                    return status === '' || value.trim() === status;
                });

                table.draw();
            }

            searchInput.addEventListener('input', filterProducts);
            categoryInput.addEventListener('change', filterProducts);
            statusInput.addEventListener('change', filterProducts);

            pageLengthInput.addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });

            filterProducts();

        });
    </script>
@endpush