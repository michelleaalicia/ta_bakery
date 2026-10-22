@extends('layouts.admin')

@section('title', 'Cabang')
@section('page-title', 'Cabang')

@section('content')

    <style>
        #branchesTable th,
        #branchesTable td {
            vertical-align: middle;
        }

        #branchesTable th:first-child,
        #branchesTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #branchesTable th:nth-child(4),
        #branchesTable td:nth-child(4) {
            text-align: left !important;
        }

        #branchesTable td:last-child {
            white-space: nowrap;
        }

        #branchesTable thead th .dt-column-order {
            display: none !important;
        }

        #branchesTable thead th.dt-orderable-asc,
        #branchesTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #branchesTable thead th.dt-orderable-asc::after,
        #branchesTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #branchesTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #branchesTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('branches.create') }}" class="btn btn-dark btn-sm">
                Tambah Cabang
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
                    <label for="searchBranch" class="form-label">
                        Cari Cabang
                    </label>

                    <input type="text" id="searchBranch" class="form-control" placeholder="Cari nama atau alamat...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="branchesTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Cabang</th>
                            <th>Alamat</th>
                            <th>No. Telepon</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $branch->name }}</td>

                                <td>{{ $branch->address }}</td>

                                <td>{{ $branch->phone_number }}</td>

                                <td>
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus cabang ini?')">

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

            const table = new DataTable('#branchesTable', {
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
                        targets: [0, 4],
                        orderable: false
                    },
                    {
                        targets: [0, 3, 4],
                        searchable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 3,
                        className: 'text-start'
                    },
                    {
                        targets: 4,
                        width: '150px'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada cabang',
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

            document.getElementById('searchBranch')
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