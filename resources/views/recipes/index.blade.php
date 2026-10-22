@extends('layouts.admin')

@section('title', 'Resep')
@section('page-title', 'Resep')

@section('content')

    <style>
        #recipesTable th,
        #recipesTable td {
            vertical-align: middle;
        }

        #recipesTable th:first-child,
        #recipesTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #recipesTable td:last-child {
            white-space: nowrap;
        }

        #recipesTable .dt-type-numeric {
            text-align: left !important;
        }

        #recipesTable thead th .dt-column-order {
            display: none !important;
        }

        #recipesTable thead th.dt-orderable-asc,
        #recipesTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #recipesTable thead th.dt-orderable-asc::after,
        #recipesTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #recipesTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #recipesTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

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

    <div class="card">

        <div class="card-header">
            <a href="{{ route('recipes.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus"></i>
                Tambah
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
                    <label for="searchRecipe" class="form-label">
                        Cari Resep
                    </label>

                    <input type="text" id="searchRecipe" class="form-control" placeholder="Cari produk atau varian...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="recipesTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Hasil Resep</th>
                            <th>Bahan Baku</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($recipes as $recipe)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $recipe->productVariant->product->name }}
                                </td>

                                <td>
                                    {{ $recipe->productVariant->name }}
                                </td>

                                <td data-order="{{ $recipe->quantity }}">
                                    {{ rtrim(rtrim(number_format($recipe->quantity, 2, ',', '.'), '0'), ',') }}
                                    {{ $recipe->unit }}
                                </td>

                                <td data-order="{{ $recipe->ingredients->count() }}">
                                    {{ $recipe->ingredients->count() }} bahan
                                </td>

                                <td>
                                    <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>
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

            const table = new DataTable('#recipesTable', {
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
                        targets: [0, 5],
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [3, 4],
                        searchable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 5,
                        width: '150px'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada resep',
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

            document.getElementById('searchRecipe')
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