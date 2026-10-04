@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')

@section('page-title', 'Manajemen Pengguna')

@section('content')

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">

        <div class="card-header">
            <div>
                <a href="{{ route('users.import') }}" class="btn btn-dark btn-sm">
                    Import Pengguna
                </a>

                <a href="{{ route('users.create') }}" class="btn btn-dark btn-sm">
                    Tambah Pengguna
                </a>

                <a href="{{ route('roles.index') }}" class="btn btn-dark btn-sm">
                    Kelola Role
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="mb-3" id="filterForm">

                <div class="row g-2">

                    {{-- Search --}}
                    <div class="col-md-4">
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Cari nama atau email">
                    </div>

                    {{-- Filter Role --}}
                    <div class="col-md-2">
                        <select name="role_id" id="role_id" class="form-select">
                            <option value="">Semua Role</option>

                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </form>

            <script>
                const searchInput = document.getElementById('search');
                const roleSelect = document.getElementById('role_id');

                function filterTable() {
                    const search = searchInput.value.toLowerCase();
                    const role = roleSelect.value;

                    document.querySelectorAll('tbody tr[data-role-id]').forEach(row => {
                        const name = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();

                        const matchSearch = name.includes(search) || email.includes(search);
                        const matchRole = role === '' || row.dataset.roleId === role;

                        row.style.display = matchSearch && matchRole ? '' : 'none';
                    });
                }

                searchInput.addEventListener('input', filterTable);
                roleSelect.addEventListener('change', filterTable);

                // cegah Enter me-reload halaman
                document.getElementById('filterForm').addEventListener('submit', e => e.preventDefault());
            </script>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">

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

                        @forelse ($users as $user)

                            <tr data-role-id="{{ $user->role_id }}">

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

                        @empty

                            <tr>
                                <td colspan="8" class="text-center">
                                    Belum ada data pengguna.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </div>

    </div>

@endsection