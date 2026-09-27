@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Produk</h3>

            <a href="{{ route('products.create') }}" class="btn btn-dark btn-sm">
                Tambah Produk
            </a>
        </div>

        <div class="card-body">

            <table class="table table-bordered table-hover">

                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($products as $product)

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td>{{ $product->description ?? '-' }}</td>

                            <td>
                                @if ($product->status === 'active')
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('products.show', $product) }}" class="btn btn-info btn-sm">
                                    Detail
                                </a>

                                <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Hapus produk ini?')">
                                        Hapus
                                    </button>

                                </form>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada produk.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

@endsection