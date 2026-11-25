@extends('layouts.admin')

@section('title', 'Kelola Role')
@section('page-title', 'Kelola Role')

@section('content')
<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="mb-3">
            <a href="{{ route('roles.create') }}" class="btn btn-dark btn-sm">Tambah Role</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle w-100">
                <thead>
                    <tr>
                        <th style="width:55px">No</th>
                        <th style="width:130px">Nama Role</th>
                        <th>Permission</th>
                        <th style="width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                @forelse ($role->modules->filter(fn($module) => $module->name !== 'Job Description') as $module)
                                    <span class="badge text-bg-light">{{ $module->name }}</span>
                                @empty
                                    <span class="text-muted">Tidak ada permission yang ditampilkan</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 flex-nowrap">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning btn-sm text-nowrap">Edit</a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="m-0"
                                          onsubmit="return confirm('Hapus role ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm text-nowrap">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">Belum ada role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
</div>
@endsection
