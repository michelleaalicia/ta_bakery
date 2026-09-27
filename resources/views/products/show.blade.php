@extends('layouts.admin')

@section('title', 'Detail Produk')
@section('page-title', 'Detail Produk')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Detail Produk</h3>
        </div>

        <div class="card-body">

            {{-- Informasi Produk --}}
            <h5 class="mb-3">Informasi Produk</h5>

            <div class="row mb-4">

                <div class="col-md-6 mb-3">
                    <strong>Nama Produk</strong>
                    <div>{{ $product->name }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Kategori</strong>
                    <div>{{ $product->category->name }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Status</strong>
                    <div>
                        @if ($product->status === 'active')
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Tidak Aktif</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Deskripsi</strong>
                    <div>
                        {{ $product->description ?: '-' }}
                    </div>
                </div>

            </div>


            {{-- Varian Produk --}}
            <h5 class="mb-3">Varian Produk</h5>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Varian</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($product->variants as $variant)

                            <tr>

                                <td style="width: 100px;">

                                    @if ($variant->image)

                                        <img src="{{ asset('storage/product_variants/' . $variant->image) }}"
                                            alt="{{ $variant->name }}" width="70" height="70"
                                            style="object-fit: cover; border-radius: 8px;">

                                    @else

                                        <span class="text-muted">
                                            Tidak ada gambar
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    {{ $variant->name }}
                                </td>

                                <td>

                                    @if ($variant->status === 'active')

                                        <span class="badge bg-success">
                                            Aktif
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Tidak Aktif
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="text-center text-muted">

                                    Belum ada varian produk.

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        <div class="card-footer">

            <a href="{{ route('products.index') }}" class="btn btn-secondary">

                Kembali

            </a>

            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-dark">

                Edit

            </a>

        </div>

    </div>

@endsection