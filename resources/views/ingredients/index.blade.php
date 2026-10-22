@extends('layouts.admin')

@section('title', 'Bahan Baku')
@section('page-title', 'Bahan Baku')

@section('content')

    <style>
        #ingredientsTable th,
        #ingredientsTable td {
            vertical-align: middle;
        }

        #ingredientsTable th:first-child,
        #ingredientsTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #ingredientsTable td:last-child {
            white-space: nowrap;
        }

        #ingredientsTable thead th .dt-column-order {
            display: none !important;
        }

        #ingredientsTable thead th.dt-orderable-asc,
        #ingredientsTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #ingredientsTable thead th.dt-orderable-asc::after,
        #ingredientsTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #ingredientsTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #ingredientsTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }

        #ingredientsTable .dt-type-numeric {
            text-align: left !important;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('ingredient-restocks.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus"></i>
                Restock
            </a>

            <a href="{{ route('ingredients.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus"></i>
                Tambah
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
                    <label for="filterBranch" class="form-label">
                        Cabang
                    </label>

                    <select id="filterBranch" class="form-select">
                        <option value="">Semua Cabang</option>

                        @foreach ($ingredients->pluck('branch.name')->filter()->unique()->sort() as $branch)
                            <option value="{{ $branch }}">
                                {{ $branch }}
                            </option>
                        @endforeach

                        @if ($ingredients->contains(fn($ingredient) => $ingredient->branch === null))
                            <option value="Tanpa Cabang">Tanpa Cabang</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">
                        Status Stok
                    </label>

                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Aman">Aman</option>
                        <option value="Menipis">Menipis</option>
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
                    <label for="searchIngredient" class="form-label">
                        Cari Bahan Baku
                    </label>

                    <input type="text" id="searchIngredient" class="form-control" placeholder="Cari nama bahan...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="ingredientsTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Bahan</th>
                            <th>Cabang</th>
                            <th>Satuan</th>
                            <th>Harga/Satuan</th>
                            <th>Stok</th>
                            <th>Min. Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($ingredients as $ingredient)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $ingredient->name }}</td>

                                <td>{{ $ingredient->branch?->name ?? 'Tanpa Cabang' }}</td>

                                <td>{{ $ingredient->unit }}</td>

                                <td data-order="{{ $ingredient->unit_cost }}">
                                    Rp {{ number_format($ingredient->unit_cost, 0, ',', '.') }}
                                </td>

                                <td data-order="{{ $ingredient->stock }}">
                                    {{ number_format($ingredient->stock, 0, ',', '.') }}
                                </td>

                                <td data-order="{{ $ingredient->min_stock }}">
                                    {{ number_format($ingredient->min_stock, 0, ',', '.') }}
                                </td>

                                <td>
                                    @if ($ingredient->stock <= $ingredient->min_stock)
                                        <span class="badge bg-secondary">
                                            Menipis
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border">
                                            Aman
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('ingredients.edit', $ingredient) }}" class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('ingredients.destroy', $ingredient) }}" method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus bahan baku ini?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
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

            const table = new DataTable('#ingredientsTable', {
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
                        targets: [0, 8],
                        orderable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 8,
                        width: '150px',
                        searchable: false
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada bahan baku',
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

            const searchInput = document.getElementById('searchIngredient');
            const branchInput = document.getElementById('filterBranch');
            const statusInput = document.getElementById('filterStatus');
            const pageLengthInput = document.getElementById('pageLength');

            function filterIngredients() {
                const keyword = searchInput.value;
                const branch = branchInput.value;
                const status = statusInput.value;

                table.column(1).search(keyword);

                table.column(2).search(function (value) {
                    return branch === '' || value.trim() === branch;
                });

                table.column(7).search(function (value) {
                    return status === '' || value.trim() === status;
                });

                table.draw();
            }

            searchInput.addEventListener('input', filterIngredients);
            branchInput.addEventListener('change', filterIngredients);
            statusInput.addEventListener('change', filterIngredients);

            pageLengthInput.addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });

            filterIngredients();

        });
    </script>
@endpush