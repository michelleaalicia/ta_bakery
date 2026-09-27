@extends('layouts.admin')

@section('title', 'Cabang')
@section('page-title', 'Cabang')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Cabang</h3>

            <a href="{{ route('branches.create') }}" class="btn btn-dark btn-sm">
                Tambah Cabang
            </a>
        </div>

        <div class="card-body">

            <table class="table table-bordered table-hover">

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

                    @forelse ($branches as $branch)

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->address }}</td>
                            <td>{{ $branch->phone_number }}</td>

                            <td>
                                <a href="{{ route('branches.show', $branch) }}" class="btn btn-info btn-sm">
                                    Detail
                                </a>

                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Hapus cabang ini?')">
                                        Hapus
                                    </button>

                                </form>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada cabang.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

@endsection