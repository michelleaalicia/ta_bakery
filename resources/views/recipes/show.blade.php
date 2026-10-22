@extends('layouts.admin')

@section('title', 'Detail Resep')
@section('page-title', 'Detail Resep')

@section('content')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">

        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Produk</strong>
                </div>

                <div class="col-md-9">
                    {{ $recipe->productVariant->product->name }}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Varian</strong>
                </div>

                <div class="col-md-9">
                    {{ $recipe->productVariant->name }}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Hasil Resep</strong>
                </div>

                <div class="col-md-9">
                    {{ rtrim(rtrim(number_format($recipe->quantity, 2, ',', '.'), '0'), ',') }}
                    {{ $recipe->unit }}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Waktu Produksi</strong>
                </div>

                <div class="col-md-9">
                    {{ rtrim(rtrim(number_format($recipe->production_time, 2, ',', '.'), '0'), ',') }}
                    menit
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <strong>Langkah Pembuatan</strong>
                </div>

                <div class="col-md-9" style="white-space: pre-line;">{{ $recipe->steps }}</div>
            </div>

            <h5 class="mb-3">Bahan Baku</h5>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($recipe->ingredients as $ingredient)
                            <tr>
                                <td>{{ $ingredient->name }}</td>

                                <td>
                                    {{ rtrim(rtrim(number_format($ingredient->pivot->quantity, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td>{{ $ingredient->pivot->unit }}</td>
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