@extends('layouts.admin')

@section('title', 'Kategori Produk')
@section('page-title', 'Kategori Produk')

@section('content')

    <div class="card">

        <div class="card-header">
            <a href="{{ route('categories.create') }}" class="btn btn-dark btn-sm">
                Tambah Kategori
            </a>
        </div>

        <div class="card-body">

            <table class="table table-bordered table-hover">

                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th style="width: 350px;">Nama Kategori</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $category->name }}</td>

                            <td>
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Hapus kategori ini?')">
                                        Hapus
                                    </button>

                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">
                                Belum ada kategori.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>
    </div>

@endsection