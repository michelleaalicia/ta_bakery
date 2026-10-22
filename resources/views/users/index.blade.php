@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')

    <style>
        #usersTable th,
        #usersTable td {
            vertical-align: middle;
        }

        #usersTable th:first-child,
        #usersTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #usersTable td:last-child {
            white-space: nowrap;
        }

        #usersTable thead th .dt-column-order {
            display: none !important;
        }

        #usersTable thead th.dt-orderable-asc,
        #usersTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #usersTable thead th.dt-orderable-asc::after,
        #usersTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #usersTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #usersTable thead th.dt-ordering-desc::after {
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

    <div class="card">

        <div class="card-header">
            <a href="{{ route('users.import') }}" class="btn btn-dark btn-sm">
                Import Pengguna
            </a>

            <a href="{{ route('users.create') }}" class="btn btn-dark btn-sm">
                Tambah Pengguna
            </a>

            <a href="{{ route('roles.index') }}" class="btn btn-dark btn-sm">
                Kelola Role
            </a>

            <a href="{{ route('job-descriptions.index') }}" class="btn btn-dark btn-sm">
                Kelola Pekerjaan
            </a>
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-2">
                    <label for="filterRole" class="form-label">
                        Role
                    </label>

                    <select id="filterRole" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="Owner">Owner</option>

                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">
                                {{ $role->name }}
                            </option>
                        @endforeach
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
                    <label for="searchUser" class="form-label">
                        Cari Pengguna
                    </label>

                    <input type="text" id="searchUser" class="form-control" placeholder="Cari nama atau email...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="usersTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Cabang</th>
                            <th>Tarif Upah/Jam</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $user->name }}</td>

                                <td>{{ $user->email }}</td>

                                <td>{{ $user->role?->name ?? '-' }}</td>

                                <td>{{ $user->branch?->name ?? '-' }}</td>

                                <td>
                                    @if ($user->role?->name === 'Owner')
                                        -
                                    @else
                                        Rp {{ number_format($user->wage_rate_per_hour, 0, ',', '.') }}
                                    @endif
                                </td>

                                <td>
                                    @if ($user->status === 'active')
                                        <span class="badge text-bg-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($user->role?->name !== 'Owner')

                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('users.reset-password', $user) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Reset password pengguna ini?')">

                                            @csrf

                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                Reset Password
                                            </button>
                                        </form>

                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">

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

            const table = new DataTable('#usersTable', {
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
                        orderable: false
                    },
                    {
                        targets: [0, 4, 5, 6, 7],
                        searchable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 7,
                        width: '260px'
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data pengguna',
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

            document.getElementById('searchUser')
                .addEventListener('input', function () {
                    table.search(this.value).draw();
                });

            document.getElementById('filterRole')
                .addEventListener('change', function () {
                    const role = this.value;

                    table.column(3).search(function (value) {
                        return role === '' || value.trim() === role;
                    }).draw();
                });

            document.getElementById('pageLength')
                .addEventListener('change', function () {
                    table.page.len(Number(this.value)).draw();
                });

        });
    </script>
@endpush