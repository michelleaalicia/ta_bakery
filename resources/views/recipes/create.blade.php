@extends('layouts.admin')

@section('title', 'Tambah Resep')
@section('page-title', 'Tambah Resep')

@section('content')

    <style>
        .ingredient-row .btn-remove {
            height: 38px;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ingredient-unit {
            background-color: #f1f3f5 !important;
        }
    </style>

    <div class="card">
        <form method="POST" action="{{ route('recipes.store') }}">
            @csrf

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="product_id" class="form-label">Produk</label>

                    <select id="product_id" class="form-select" required>
                        <option value="">Pilih Produk</option>

                        @foreach ($variants->pluck('product')->filter()->unique('id')->sortBy('name') as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="product_variant_id" class="form-label">Varian</label>

                    <select name="product_variant_id" id="product_variant_id" class="form-select" required disabled>

                        <option value="">Pilih Produk Terlebih Dahulu</option>

                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}" data-product-id="{{ $variant->product_id }}">
                                {{ $variant->name }}
                            </option>
                        @endforeach

                    </select>

                    @error('product_variant_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Hasil Resep</label>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="number" name="quantity" class="form-control" min="0.01" step="0.01"
                                value="{{ old('quantity') }}" placeholder="Contoh: 20" required>
                        </div>

                        <div class="col-md-6">
                            <select name="unit" class="form-select" required>
                                <option value="">Pilih Satuan</option>

                                @foreach (['pcs', 'loyang', 'bungkus', 'box', 'toples', 'kg', 'gram', 'liter', 'botol'] as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit') === $unit)>
                                        {{ $unit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <small class="text-muted">
                        Jumlah produk yang dihasilkan dari satu kali resep.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Waktu Produksi</label>

                    <div class="input-group">
                        <input type="number" name="production_time" class="form-control" min="1" step="1"
                            value="{{ old('production_time') }}" placeholder="Contoh: 60" required>

                        <span class="input-group-text">menit</span>
                    </div>

                    <small class="text-muted">
                        Waktu yang dibutuhkan untuk menghasilkan satu kali resep.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Langkah Pembuatan</label>

                    <textarea name="steps" class="form-control" rows="3" placeholder="Tulis langkah-langkah pembuatan..."
                        required>{{ old('steps') }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Bahan Baku</label>

                    <button type="button" class="btn btn-dark btn-sm" onclick="addIngredient()">
                        <i class="bi bi-plus"></i>
                        Tambah Bahan
                    </button>
                </div>

                <div id="ingredient-container">

                    @foreach (old('ingredients', [['ingredient_id' => '', 'quantity' => '']]) as $index => $oldIngredient)
                        <div class="ingredient-row border rounded p-3 mb-2">

                            <div class="row g-3 align-items-end">

                                <div class="col-md-6">
                                    <label class="form-label">Bahan</label>

                                    <select name="ingredients[{{ $index }}][ingredient_id]"
                                        class="form-select ingredient-select" onchange="updateUnit(this)" required>

                                        <option value="">Pilih Bahan</option>

                                        @foreach ($ingredients as $ingredient)
                                            <option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unit }}"
                                                @selected(($oldIngredient['ingredient_id'] ?? '') == $ingredient->id)>
                                                {{ $ingredient->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Jumlah</label>

                                    <input type="number" name="ingredients[{{ $index }}][quantity]" class="form-control"
                                        min="0.01" step="0.01" value="{{ $oldIngredient['quantity'] ?? '' }}"
                                        placeholder="Jumlah bahan" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Satuan</label>

                                    <input type="text" class="form-control ingredient-unit" readonly>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-remove"
                                        onclick="removeIngredient(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            const variantSelect = document.getElementById('product_variant_id');

            const variants = Array.from(variantSelect.querySelectorAll('option'))
                .filter(option => option.value)
                .map(option => option.cloneNode(true));

            const oldVariantId = @json(old('product_variant_id'));

            function updateVariants(selectedId = '') {
                const productId = productSelect.value;

                variantSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = productId
                    ? 'Pilih Varian'
                    : 'Pilih Produk Terlebih Dahulu';

                variantSelect.appendChild(placeholder);
                variantSelect.disabled = !productId;

                variants.forEach(function (option) {
                    if (option.dataset.productId === productId) {
                        variantSelect.appendChild(option.cloneNode(true));
                    }
                });

                if (selectedId) {
                    variantSelect.value = String(selectedId);
                }
            }

            productSelect.addEventListener('change', function () {
                updateVariants();
            });

            if (oldVariantId) {
                const oldVariant = variants.find(option =>
                    option.value === String(oldVariantId)
                );

                if (oldVariant) {
                    productSelect.value = oldVariant.dataset.productId;
                    updateVariants(oldVariantId);
                }
            } else {
                updateVariants();
            }

            document.querySelectorAll('.ingredient-select').forEach(function (select) {
                updateUnit(select);
            });
        });

        let ingredientIndex = 1000;

        function addIngredient() {
            const container = document.getElementById('ingredient-container');
            const firstRow = container.querySelector('.ingredient-row');

            if (!firstRow) {
                return;
            }

            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('select, input').forEach(function (field) {
                if (field.name) {
                    field.name = field.name.replace(
                        /ingredients\[\d+\]/,
                        'ingredients[' + ingredientIndex + ']'
                    );
                }

                field.value = '';
            });

            container.appendChild(newRow);
            ingredientIndex++;
        }

        function removeIngredient(button) {
            const rows = document.querySelectorAll('.ingredient-row');

            if (rows.length <= 1) {
                alert('Resep harus memiliki minimal satu bahan baku.');
                return;
            }

            button.closest('.ingredient-row').remove();
        }

        function updateUnit(select) {
            const option = select.options[select.selectedIndex];
            const unit = option?.dataset.unit ?? '';

            select.closest('.ingredient-row')
                .querySelector('.ingredient-unit').value = unit;
        }
    </script>

@endsection