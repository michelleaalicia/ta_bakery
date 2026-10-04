@extends('layouts.admin')

@section('title', 'Resep')
@section('page-title', 'Resep')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

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
                            <th style="width: 60px;">No</th>
                            <th>Produk - Varian</th>
                            <th>Hasil Resep</th>
                            <th>Bahan Baku</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($recipes as $recipe)

                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $recipe->productVariant->product->name }}
                                    -
                                    {{ $recipe->productVariant->name }}
                                </td>

                                <td>
                                    {{ rtrim(rtrim(number_format($recipe->quantity, 2, ',', '.'), '0'), ',') }}
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
                                <td colspan="5" class="text-center text-muted">

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