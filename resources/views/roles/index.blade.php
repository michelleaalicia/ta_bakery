@extends('layouts.admin')

@section('title', 'Kelola Role')
@section('page-title', 'Kelola Role')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <a href="{{ route('roles.create') }}" class="btn btn-dark btn-sm">
                Tambah Role
            </a>
        </div>

        <div class="card-body">

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

        </div>
    </div>

@endsection