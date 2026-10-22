@extends('layouts.admin')

@section('title', 'Kategori Produk')
@section('page-title', 'Kategori Produk')

@section('content')

    <style>
        #categoriesTable th,
        #categoriesTable td {
            vertical-align: middle;
        }

        #categoriesTable th:first-child,
        #categoriesTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #categoriesTable th:last-child,
        #categoriesTable td:last-child {
            width: 150px !important;
            white-space: nowrap;
        }

        #categoriesTable thead th .dt-column-order {
            display: none !important;
        }

        #categoriesTable thead th.dt-orderable-asc,
        #categoriesTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #categoriesTable thead th.dt-orderable-asc::after,
        #categoriesTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #categoriesTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #categoriesTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('categories.create') }}" class="btn btn-dark btn-sm">
                Tambah Kategori
            </a>
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end mb-3">

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
                    <label for="searchCategory" class="form-label">
                        Cari Kategori
                    </label>

                    <input type="text" id="searchCategory" class="form-control" placeholder="Cari nama kategori...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="categoriesTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $category->name }}</td>

                                <td>
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus kategori ini?')">

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

            const table = new DataTable('#categoriesTable', {
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
                        targets: [0, 2],
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 2,
                        width: '150px'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada kategori',
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

            document.getElementById('searchCategory')
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