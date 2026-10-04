@extends('layouts.admin')

@section('title', 'Detail Produk')
@section('page-title', 'Detail Produk')

@section('content')

    <div class="card">

        <div class="card-body">

            {{-- Informasi Produk --}}
            <div class="mb-4">

                <div class="mb-3">
                    <strong>Nama Produk</strong>
                    <div>{{ $product->name }}</div>
                </div>

                <div class="mb-3">
                    <strong>Kategori</strong>
                    <div>{{ $product->category->name }}</div>
                </div>

                <div class="mb-3">
                    <strong>Status</strong>
                    <div>
                        @if ($product->status === 'active')
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Tidak Aktif</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
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
                            <th style="width: 100px;">Gambar</th>
                            <th>Nama Varian</th>
                            <th>Status</th>
                            <th style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($product->variants as $variant)

                            <tr>

                                <td>
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

                                <td>
                                    <a href="{{ route('product-variants.edit', [$product, $variant]) }}"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('product-variants.destroy', [$product, $variant]) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Yakin ingin menghapus varian ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    Belum ada varian produk.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        {{-- Tombol --}}
        <div class="card-footer">

            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Kembali
            </a>
        </div>

    </div>

@endsection