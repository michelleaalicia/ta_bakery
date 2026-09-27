@extends('layouts.admin')

@section('title', 'Resep')
@section('page-title', 'Resep')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h3 class="card-title">Daftar Resep</h3>

            <a href="{{ route('recipes.create') }}" class="btn btn-dark btn-sm">

                <i class="bi bi-plus"></i>
                Tambah

            </a>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th>Produk - Varian</th>
                            <th>Hasil Resep</th>
                            <th>Jumlah Bahan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($recipes as $recipe)

                            <tr>

                                <td>
                                    {{ $recipe->productVariant->product->name }}
                                    -
                                    {{ $recipe->productVariant->name }}
                                </td>

                                <td>
                                    {{ $recipe->quantity }}
                                    {{ $recipe->unit }}
                                </td>

                                <td>
                                    {{ $recipe->ingredients()->count() }}
                                    bahan
                                </td>

                                <td>

                                    <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center text-muted">

                                    Belum ada resep.

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection