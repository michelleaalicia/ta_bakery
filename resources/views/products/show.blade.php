@extends('layouts.admin')

@section('title', 'Detail Produk')
@section('page-title', 'Detail Produk')

@section('content')

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h5 class="mb-1">{{ $product->name }}</h5>
                    <div class="text-muted">{{ $product->category?->name ?? 'Tanpa kategori' }}</div>
                </div>

                <div>
                    @if ($product->status === 'active')
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </div>
            </div>

            @if ($product->description)
                <div class="mb-4">
                    <div class="text-muted small mb-1">Deskripsi</div>
                    <div>{{ $product->description }}</div>
                </div>
            @endif

            <hr>


            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Varian Produk</h5>
                </div>

                <button type="button" class="btn btn-dark btn-sm" onclick="toggleFormVarian()">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Varian
                </button>
            </div>

            <div id="formTambahVarian" style="{{ $errors->any() ? '' : 'display: none;' }}">

                <form action="{{ route('product-variants.store', $product) }}" method="POST" enctype="multipart/form-data"
                    class="border rounded p-3 mb-3 bg-light">

                    @csrf

                    <h6 class="fw-semibold mb-3">Tambah Varian Baru</h6>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label for="variantName" class="form-label">
                                Nama Varian
                            </label>
                            <input type="text" id="variantName" name="name" value="{{ old('name') }}" class="form-control"
                                placeholder="Contoh: Keju" maxlength="255" required>
                        </div>

                        <div class="col-md-3">
                            <label for="variantStatus" class="form-label">
                                Status
                            </label>
                            <select id="variantStatus" name="status" class="form-select" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>
                                    Aktif
                                </option>
                                <option value="inactive" @selected(old('status') === 'inactive')>
                                    Nonaktif
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="variantImage" class="form-label">
                                Gambar (Opsional)
                            </label>
                            <input type="file" id="variantImage" name="image" class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-dark btn-sm">
                            Simpan Varian
                        </button>

                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleFormVarian()">
                            Batal
                        </button>
                    </div>

                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;">Gambar</th>
                            <th>Nama Varian</th>
                            <th style="width: 150px;">Harga Jual</th>
                            <th>Stok Produk Jadi</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->variants as $variant)
                            <tr>
                                <td>
                                    @if ($variant->image)
                                        <img src="{{ asset('storage/product_variants/' . $variant->image) }}"
                                            alt="{{ $variant->name }}" width="64" height="64"
                                            style="object-fit: cover; border-radius: 8px;">
                                    @else
                                        <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded"
                                            style="width: 64px; height: 64px; font-size: 12px;">
                                            Tanpa gambar
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $variant->name }}</td>
                                <td>Rp {{ number_format((float) $variant->price, 0, ',', '.') }}</td>
                                <td>
                                    @if ($branches->isNotEmpty())
                                        <div class="d-flex flex-column gap-2">
                                            @foreach ($branches as $branch)
                                                @php
                                                    $stockRecord = $variant->stocks->firstWhere('branch_id', $branch->id);
                                                    $stockAmount = $stockRecord?->stock ?? 0;
                                                @endphp
                                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                                    <span class="text-muted">{{ $branch->name }}</span>
                                                    <span
                                                        class="badge {{ (float) $stockAmount > 0 ? 'bg-light text-dark border' : 'bg-secondary' }}">
                                                        {{ rtrim(rtrim(number_format((float) $stockAmount, 2, ',', '.'), '0'), ',') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        @php
                                            $stockRecord = $variant->stocks->firstWhere('branch_id', null);
                                            $stockAmount = $stockRecord?->stock ?? 0;
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <span class="text-muted">Tanpa cabang</span>
                                            <span
                                                class="badge {{ (float) $stockAmount > 0 ? 'bg-light text-dark border' : 'bg-secondary' }}">
                                                {{ rtrim(rtrim(number_format((float) $stockAmount, 2, ',', '.'), '0'), ',') }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($variant->status === 'active')
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('product-variants.edit', [$product, $variant]) }}"
                                        class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('product-variants.destroy', [$product, $variant]) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Yakin ingin menghapus varian ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada varian produk untuk ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function toggleFormVarian() {
            const form = document.getElementById('formTambahVarian');

            if (!form) return;

            form.style.display =
                form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush