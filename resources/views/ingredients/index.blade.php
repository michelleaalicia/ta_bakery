@extends('layouts.admin')

@section('title', 'Bahan Baku')
@section('page-title', 'Bahan Baku')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h3 class="card-title">Bahan Baku</h3>

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

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Cabang</th>
                            <th>Satuan</th>
                            <th>Harga/Satuan</th>
                            <th>Stok</th>
                            <th>Min. Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($ingredients as $ingredient)

                            <tr>

                                <td>{{ $ingredient->name }}</td>

                                <td>{{ $ingredient->branch->name }}</td>

                                <td>{{ $ingredient->unit }}</td>

                                <td>
                                    Rp {{ number_format($ingredient->unit_cost, 0, ',', '.') }}
                                </td>

                                <td>{{ $ingredient->stock }}</td>

                                <td>{{ $ingredient->min_stock }}</td>

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

                                <td>

                                    <a href="{{ route('ingredients.edit', $ingredient->id) }}" class="btn btn-sm btn-link">
                                        Ubah
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="text-center text-muted">
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