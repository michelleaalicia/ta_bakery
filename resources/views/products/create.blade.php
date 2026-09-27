@extends('layouts.admin')

@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Tambah Produk</h3>
        </div>

        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">

            @csrf

            <div class="card-body">

                {{-- Nama Produk --}}
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>

                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Nama produk"
                        required>
                </div>


                {{-- Kategori --}}
                <div class="mb-3">
                    <label class="form-label">Kategori</label>

                    <select name="category_id" class="form-select" required>

                        <option value="">Pilih Kategori</option>

                        @foreach ($categories as $category)

                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>

                                {{ $category->name }}

                            </option>

                        @endforeach

                    </select>
                </div>


                {{-- Deskripsi --}}
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>

                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Deskripsi produk">{{ old('description') }}</textarea>
                </div>


                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label">Status</label>

                    <select name="status" class="form-select" required>

                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                            Tidak Aktif
                        </option>

                    </select>
                </div>


                {{-- VARIAN PRODUK --}}
                <div class="mb-3">

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <label class="form-label mb-0">
                            Varian Produk
                        </label>

                        <button type="button" class="btn btn-sm btn-dark" onclick="addVariant()">

                            <i class="bi bi-plus"></i>
                            Tambah Varian

                        </button>

                    </div>


                    <div id="variant-container">

                        {{-- Variant pertama --}}
                        <div class="variant-row border rounded p-3 mb-2">

                            <div class="row">

                                {{-- Nama Variant --}}
                                <div class="col-md-5">

                                    <label class="form-label">
                                        Nama Varian
                                    </label>

                                    <input type="text" name="variants[0][name]" class="form-control"
                                        placeholder="Nama varian" required>

                                </div>


                                {{-- Status --}}
                                <div class="col-md-3">

                                    <label class="form-label">
                                        Status
                                    </label>

                                    <select name="variants[0][status]" class="form-select">

                                        <option value="active">
                                            Aktif
                                        </option>

                                        <option value="inactive">
                                            Tidak Aktif
                                        </option>

                                    </select>

                                </div>


                                {{-- Gambar --}}
                                <div class="col-md-3">

                                    <label class="form-label">
                                        Gambar
                                    </label>

                                    <input type="file" name="variants[0][image]" class="form-control" accept="image/*">

                                </div>


                                {{-- Hapus --}}
                                <div class="col-md-1 d-flex align-items-end">

                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removeVariant(this)">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Footer --}}
            <div class="card-footer">

                <a href="{{ route('products.index') }}" class="btn btn-secondary">

                    Kembali

                </a>

                <button type="submit" class="btn btn-dark">

                    Simpan

                </button>

            </div>

        </form>

    </div>


    <script>

        let variantIndex = 1;


        function addVariant() {
            let html = `

                <div class="variant-row border rounded p-3 mb-2">

                    <div class="row">

                        <div class="col-md-5">

                            <label class="form-label">
                                Nama Varian
                            </label>

                            <input type="text"
                                name="variants[${variantIndex}][name]"
                                class="form-control"
                                placeholder="Nama varian"
                                required>

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="variants[${variantIndex}][status]"
                                class="form-select">

                                <option value="active">
                                    Aktif
                                </option>

                                <option value="inactive">
                                    Tidak Aktif
                                </option>

                            </select>

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Gambar
                            </label>

                            <input type="file"
                                name="variants[${variantIndex}][image]"
                                class="form-control"
                                accept="image/*">

                        </div>


                        <div class="col-md-1 d-flex align-items-end">

                            <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="removeVariant(this)">

                                <i class="bi bi-trash"></i>

                            </button>

                        </div>

                    </div>

                </div>

            `;

            document
                .getElementById('variant-container')
                .insertAdjacentHTML('beforeend', html);

            variantIndex++;
        }


        function removeVariant(button) {
            button.closest('.variant-row').remove();
        }

    </script>

@endsection