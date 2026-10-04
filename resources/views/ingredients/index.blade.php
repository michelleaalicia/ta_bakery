@extends('layouts.admin')

@section('title', 'Bahan Baku')
@section('page-title', 'Bahan Baku')

@section('content')

    <div class="card">

        <div class="card-header">
            <div>
                <a href="{{ route('ingredient-restocks.create') }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-plus"></i>
                    Restock
                </a>

                <a href="{{ route('ingredients.create') }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-plus"></i>
                    Tambah
                </a>
            </div>
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
            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Bahan</th>
                            <th>Cabang</th>
                            <th>Satuan</th>
                            <th>Harga/Satuan</th>
                            <th>Stok</th>
                            <th>Min. Stok</th>
                            <th>Status</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($ingredients as $ingredient)

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $ingredient->name }}</td>

                                <td>{{ $ingredient->branch->name }}</td>

                                <td>{{ $ingredient->unit }}</td>

                                <td>
                                    Rp {{ number_format($ingredient->unit_cost, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($ingredient->stock, 0, ',', '.') }}
                                </td>

                                <td>
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

                                <td style="width: 150px;">
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

                        @empty

                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    Belum ada bahan baku.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection