@extends('layouts.admin')

@section('title', 'Kelola Role')
@section('page-title', 'Kelola Role')

@section('content')

    <div class="card">
        <div class="card-body">

            <div class="mb-3">
                <a href="{{ route('roles.create') }}" class="btn btn-dark btn-sm">
                    Tambah Role
                </a>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Role</th>
                        <th>Permission</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                @foreach ($role->modules as $module)
                                    <span class="badge text-bg-light">
                                        {{ $module->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Hapus role ini?')">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">
                                Belum ada role.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                    Kembali
                </a>
            </div>

        </div>
    </div>

@endsection