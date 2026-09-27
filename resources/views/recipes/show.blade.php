@extends('layouts.admin')

@section('title', 'Detail Resep')
@section('page-title', 'Detail Resep')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h3 class="card-title">
                Detail Resep
            </h3>

            <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-dark btn-sm">

                Ubah

            </a>

        </div>


        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <strong>Produk - Varian</strong>
                </div>

                <div class="col-md-9">

                    {{ $recipe->productVariant->product->name }}
                    /
                    {{ $recipe->productVariant->name }}

                </div>

            </div>


            <div class="row mb-3">

                <div class="col-md-3">
                    <strong>Hasil Resep</strong>
                </div>

                <div class="col-md-9">

                    {{ $recipe->quantity }} pcs

                </div>

            </div>


            <div class="row mb-4">

                <div class="col-md-3">
                    <strong>Langkah Pembuatan</strong>
                </div>

                <div class="col-md-9" style="white-space: pre-line;">{{ $recipe->steps }}</div>

            </div>


            <h5 class="mb-3">
                Bahan Baku
            </h5>


            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>
                            <th>Bahan</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($recipe->ingredients as $ingredient)

                            <tr>

                                <td>
                                    {{ $ingredient->name }}
                                </td>

                                <td>
                                    {{ $ingredient->pivot->quantity }}
                                </td>

                                <td>
                                    {{ $ingredient->pivot->unit }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        <div class="card-footer">

            <a href="{{ route('recipes.index') }}" class="btn btn-secondary">

                Kembali

            </a>

        </div>

    </div>

@endsection