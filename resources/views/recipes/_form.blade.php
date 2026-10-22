@php
    $isEdit = isset($recipe);
    $savedIngredients = $isEdit
        ? $recipe->ingredients->map(fn ($ingredient) => [
            'ingredient_id' => $ingredient->id,
            'quantity' => $ingredient->pivot->quantity,
        ])->all()
        : [];
    $ingredientRows = old('ingredients', $savedIngredients ?: [['ingredient_id' => '', 'quantity' => '']]);
    if (!$ingredientRows) {
        $ingredientRows = [['ingredient_id' => '', 'quantity' => '']];
    }
    $nextIngredientIndex = $ingredientRows ? max(array_keys($ingredientRows)) + 1 : 1;
@endphp

<div class="card-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Produk - Varian</label>
        @if ($isEdit)
            <input type="text" class="form-control"
                value="{{ $recipe->productVariant->product->name }} / {{ $recipe->productVariant->name }}" readonly>
        @else
            <select name="product_variant_id" class="form-select" required>
                <option value="">Pilih Produk - Varian</option>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>
                        {{ $variant->product->name }} / {{ $variant->name }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="mb-3">
        <label class="form-label">Hasil Resep</label>
        <div class="row">
            <div class="col-md-6">
                <input type="number" name="quantity" class="form-control"
                    min="0.01" step="0.01" placeholder="Contoh: 20"
                    value="{{ old('quantity', $isEdit ? $recipe->quantity : '') }}" required>
            </div>
            <div class="col-md-6">
                <select name="unit" class="form-select" required>
                    <option value="">Pilih Satuan</option>
                    @foreach (['pcs', 'loyang', 'bungkus', 'box', 'toples', 'kg', 'gram', 'liter', 'botol'] as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $isEdit ? $recipe->unit : '') == $unit)>
                            {{ $unit }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <small class="text-muted">Jumlah produk yang dihasilkan dari satu kali resep.</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Waktu Produksi</label>
        <div class="input-group">
            <input type="number" name="production_time" class="form-control"
                min="0.01" step="0.01" placeholder="Contoh: 60"
                value="{{ old('production_time', $isEdit ? $recipe->production_time : '') }}" required>
            <span class="input-group-text">menit</span>
        </div>
        <small class="text-muted">Waktu yang dibutuhkan untuk menghasilkan satu kali resep.</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Langkah Pembuatan</label>
        <textarea name="steps" class="form-control" rows="4" required
            placeholder="Tulis langkah-langkah pembuatan...">{{ old('steps', $isEdit ? $recipe->steps : '') }}</textarea>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Bahan Baku</label>
        <button type="button" class="btn btn-dark btn-sm" onclick="addIngredient()">
            <i class="bi bi-plus"></i> Tambah Bahan
        </button>
    </div>

    <div class="alert alert-light border small">
        Pilih bahan dari <strong>satu cabang yang sama</strong>. Untuk bakery tanpa cabang,
        pilih bahan bertanda "Tanpa Cabang". Satuan mengikuti data bahan baku.
    </div>

    <div id="ingredient-container">
        @foreach ($ingredientRows as $index => $row)
            <div class="ingredient-row border rounded p-3 mb-2">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Bahan</label>
                        <select name="ingredients[{{ $index }}][ingredient_id]"
                            class="form-select ingredient-select" onchange="updateUnit(this)" required>
                            <option value="">Pilih Bahan</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unit }}"
                                    @selected(($row['ingredient_id'] ?? '') == $ingredient->id)>
                                    {{ $ingredient->name }} - {{ $ingredient->branch?->name ?? 'Tanpa Cabang' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="ingredients[{{ $index }}][quantity]"
                            class="form-control" min="0.01" step="0.01"
                            value="{{ $row['quantity'] ?? '' }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control ingredient-unit" readonly>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger" onclick="removeIngredient(this)"
                            aria-label="Hapus bahan"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="card-footer">
    <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Batal</a>
    <button type="submit" class="btn btn-dark">Simpan</button>
</div>

<template id="ingredient-template">
    <div class="ingredient-row border rounded p-3 mb-2">
        <div class="row align-items-end">
            <div class="col-md-6">
                <label class="form-label">Bahan</label>
                <select name="ingredients[__INDEX__][ingredient_id]"
                    class="form-select ingredient-select" onchange="updateUnit(this)" required>
                    <option value="">Pilih Bahan</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unit }}">
                            {{ $ingredient->name }} - {{ $ingredient->branch?->name ?? 'Tanpa Cabang' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jumlah</label>
                <input type="number" name="ingredients[__INDEX__][quantity]"
                    class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Satuan</label>
                <input type="text" class="form-control ingredient-unit" readonly>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger" onclick="removeIngredient(this)"
                    aria-label="Hapus bahan"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    let ingredientIndex = {{ $nextIngredientIndex }};

    function addIngredient() {
        const html = document.getElementById('ingredient-template').innerHTML
            .replaceAll('__INDEX__', ingredientIndex);
        document.getElementById('ingredient-container').insertAdjacentHTML('beforeend', html);
        ingredientIndex++;
    }

    function removeIngredient(button) {
        if (document.querySelectorAll('.ingredient-row').length <= 1) {
            alert('Minimal harus ada 1 bahan baku.');
            return;
        }
        button.closest('.ingredient-row').remove();
    }

    function updateUnit(select) {
        const unit = select.selectedOptions[0]?.dataset.unit ?? '';
        select.closest('.ingredient-row').querySelector('.ingredient-unit').value = unit;
    }

    document.querySelectorAll('.ingredient-select').forEach(updateUnit);
</script>
