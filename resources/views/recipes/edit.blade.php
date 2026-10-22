@extends('layouts.admin')

@section('title', 'Edit Resep')
@section('page-title', 'Edit Resep')

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

        <form method="POST" action="{{ route('recipes.update', $recipe) }}">
            @csrf
            @method('PUT')

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
                            <option value="{{ $product->id }}" @selected($product->id == $recipe->productVariant->product_id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="product_variant_id" class="form-label">Varian</label>

                    <select name="product_variant_id" id="product_variant_id" class="form-select" required>
                        <option value="">Pilih Varian</option>
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
                                value="{{ old('quantity', $recipe->quantity) }}" placeholder="Contoh: 20" required>
                        </div>

                        <div class="col-md-6">
                            <select name="unit" class="form-select" required>
                                <option value="">Pilih Satuan</option>

                                @foreach (['pcs', 'loyang', 'bungkus', 'box', 'toples', 'kg', 'gram', 'liter', 'botol'] as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit', $recipe->unit) === $unit)>
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
                            value="{{ old('production_time', $recipe->production_time) }}" placeholder="Contoh: 60"
                            required>

                        <span class="input-group-text">menit</span>
                    </div>

                    <small class="text-muted">
                        Waktu yang dibutuhkan untuk menghasilkan satu kali resep.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Langkah Pembuatan</label>

                    <textarea name="steps" class="form-control" rows="3" placeholder="Tulis langkah-langkah pembuatan..."
                        required>{{ old('steps', $recipe->steps) }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Bahan Baku</label>

                    <button type="button" class="btn btn-dark btn-sm" onclick="addIngredient()">
                        <i class="bi bi-plus"></i>
                        Tambah Bahan
                    </button>
                </div>

                <div id="ingredient-container">

                    @php
                        $defaultIngredients = $recipe->ingredients->map(function ($ingredient) use ($ingredients) {
                            $masterIngredient = $ingredients->firstWhere(
                                'ingredient_master_id',
                                $ingredient->ingredient_master_id
                            );

                            return [
                                'ingredient_id' => $masterIngredient?->id,
                                'quantity' => $ingredient->pivot->quantity,
                            ];
                        })->toArray();
                        $formIngredients = old('ingredients', $defaultIngredients);
                    @endphp

                    @foreach ($formIngredients as $index => $formIngredient)
                        <div class="ingredient-row border rounded p-3 mb-2">

                            <div class="row g-3 align-items-end">

                                <div class="col-md-6">
                                    <label class="form-label">Bahan</label>

                                    <select name="ingredients[{{ $index }}][ingredient_id]"
                                        class="form-select ingredient-select" onchange="updateUnit(this)" required>

                                        <option value="">Pilih Bahan</option>

                                        @foreach ($ingredients as $ingredient)
                                            <option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unit }}"
                                                @selected($ingredient->id == ($formIngredient['ingredient_id'] ?? ''))>
                                                {{ $ingredient->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Jumlah</label>

                                    <input type="number" name="ingredients[{{ $index }}][quantity]" class="form-control"
                                        min="0.01" step="0.01" value="{{ $formIngredient['quantity'] ?? '' }}" required>
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
                alert('Minimal harus ada 1 bahan baku.');
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


        const productSelect = document.getElementById('product_id');
        const variantSelect = document.getElementById('product_variant_id');

        const variants = @json($variants->map(fn($variant) => [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'name' => $variant->name
        ])->values());

        const selectedVariantId = @json(old('product_variant_id', $recipe->product_variant_id));

        function updateVariants(selectedId = '') {
            variantSelect.innerHTML = '<option value="">Pilih Varian</option>';

            variants.forEach(function (variant) {
                if (String(variant.product_id) === productSelect.value) {
                    const option = document.createElement('option');
                    option.value = variant.id;
                    option.textContent = variant.name;
                    variantSelect.appendChild(option);
                }
            });

            variantSelect.value = String(selectedId);
        }

        productSelect.addEventListener('change', function () {
            updateVariants();
        });

        const selectedVariant = variants.find(variant =>
            String(variant.id) === String(selectedVariantId)
        );

        if (selectedVariant) {
            productSelect.value = String(selectedVariant.product_id);
        }

        updateVariants(selectedVariantId);

    </script>

@endsection