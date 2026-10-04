@extends('layouts.admin')

@section('title', 'Edit Varian Produk')
@section('page-title', 'Edit Varian Produk')

@section('content')

    <div class="card">
        <form action="{{ route('product-variants.update', [$product, $variant]) }}" method="POST"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <div class="card-body">

                {{-- Nama Varian --}}
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Varian
                    </label>

                    <input type="text" name="name" id="name" value="{{ old('name', $variant->name) }}"
                        class="form-control @error('name') is-invalid @enderror" placeholder="Masukkan nama varian"
                        required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label for="status" class="form-label">
                        Status
                    </label>

                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>

                        <option value="active" {{ old('status', $variant->status) === 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="inactive" {{ old('status', $variant->status) === 'inactive' ? 'selected' : '' }}>
                            Tidak Aktif
                        </option>

                    </select>

                    @error('status')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Gambar --}}
                <div class="mb-3">
                    <label for="image" class="form-label">
                        Gambar Varian
                    </label>

                    @if ($variant->image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/product_variants/' . $variant->image) }}" alt="{{ $variant->name }}"
                                width="100" height="100" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    @endif

                    <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror"
                        accept=".jpg,.jpeg,.png,.webp">

                    <div class="form-text">
                        Kosongkan jika tidak ingin mengganti gambar.
                    </div>

                    @error('image')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

            <div class="card-footer">

                <a href="{{ route('products.show', $product) }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>

            </div>

        </form>

    </div>

@endsection