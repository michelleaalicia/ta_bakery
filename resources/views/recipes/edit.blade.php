@extends('layouts.admin')

@section('title', 'Edit Resep')
@section('page-title', 'Edit Resep')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('recipes.update', $recipe) }}">
            @csrf
            @method('PUT')

            <div class="card-body">

                {{-- Produk - Varian --}}
                <div class="mb-3">
                    <label class="form-label">
                        Produk - Varian
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="{{ $recipe->productVariant->product->name }} / {{ $recipe->productVariant->name }}"
                        readonly>
                </div>

                {{-- Hasil Resep --}}
                <div class="mb-3">
                    <label class="form-label">
                        Hasil Resep
                    </label>

                    <div class="row">
                        <div class="col-md-6">
                            <input
                                type="number"
                                name="quantity"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                value="{{ old('quantity', $recipe->quantity) }}"
                                placeholder="Contoh: 20"
                                required>
                        </div>

                        <div class="col-md-6">
                            <select name="unit" class="form-select" required>
                                <option value="">
                                    Pilih Satuan
                                </option>

                                @foreach ([
                                    'pcs',
                                    'loyang',
                                    'bungkus',
                                    'box',
                                    'toples',
                                    'kg',
                                    'gram',
                                    'liter',
                                    'botol'
                                ] as $unit)
                                    <option value="{{ $unit }}"
                                        {{ old('unit', $recipe->unit) == $unit ? 'selected' : '' }}>
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

                {{-- Waktu Produksi --}}
                <div class="mb-3">
                    <label class="form-label">
                        Waktu Produksi
                    </label>

                    <div class="input-group">
                        <input
                            type="number"
                            name="production_time"
                            class="form-control"
                            min="1"
                            step="1"
                            value="{{ old('production_time', $recipe->production_time) }}"
                            placeholder="Contoh: 60"
                            required>

                        <span class="input-group-text">
                            menit
                        </span>
                    </div>

                    <small class="text-muted">
                        Waktu yang dibutuhkan untuk menghasilkan satu kali resep.
                    </small>
                </div>

                {{-- Langkah Pembuatan --}}
                <div class="mb-3">
                    <label class="form-label">
                        Langkah Pembuatan
                    </label>

                    <textarea
                        name="steps"
                        class="form-control"
                        rows="3"
                        placeholder="Tulis langkah-langkah pembuatan..."
                        required>{{ old('steps', $recipe->steps) }}</textarea>
                </div>

                {{-- Bahan Baku --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">
                        Bahan Baku
                    </label>

                    <button
                        type="button"
                        class="btn btn-dark btn-sm"
                        onclick="addIngredient()">
                        <i class="bi bi-plus"></i>
                        Tambah Bahan
                    </button>
                </div>

                <div id="ingredient-container">

                    @foreach ($recipe->ingredients as $index => $recipeIngredient)
                        <div class="ingredient-row border rounded p-3 mb-2">
                            <div class="row align-items-end">

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Bahan
                                    </label>

                                    <select
                                        name="ingredients[{{ $index }}][ingredient_id]"
                                        class="form-select ingredient-select"
                                        onchange="updateUnit(this)"
                                        required>
                                        <option value="">
                                            Pilih Bahan
                                        </option>

                                        @foreach ($ingredients as $ingredient)
                                            <option
                                                value="{{ $ingredient->id }}"
                                                data-unit="{{ $ingredient->unit }}"
                                                {{ $recipeIngredient->id == $ingredient->id ? 'selected' : '' }}>
                                                {{ $ingredient->name }}
                                                - {{ $ingredient->branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">
                                        Jumlah
                                    </label>

                                    <input
                                        type="number"
                                        name="ingredients[{{ $index }}][quantity]"
                                        class="form-control"
                                        min="0.01"
                                        step="0.01"
                                        value="{{ old('ingredients.' . $index . '.quantity', $recipeIngredient->pivot->quantity) }}"
                                        required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">
                                        Satuan
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control ingredient-unit"
                                        value="{{ $recipeIngredient->pivot->unit }}"
                                        readonly>
                                </div>

                                <div class="col-md-1">
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger"
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
        let ingredientIndex = {{ $recipe->ingredients->count() }};

        function addIngredient() {
            let html = `
                <div class="ingredient-row border rounded p-3 mb-2">
                    <div class="row align-items-end">

                        <div class="col-md-6">
                            <label class="form-label">
                                Bahan
                            </label>

                            <select
                                name="ingredients[${ingredientIndex}][ingredient_id]"
                                class="form-select ingredient-select"
                                onchange="updateUnit(this)"
                                required>
                                <option value="">
                                    Pilih Bahan
                                </option>

                                @foreach ($ingredients as $ingredient)
                                    <option
                                        value="{{ $ingredient->id }}"
                                        data-unit="{{ $ingredient->unit }}">
                                        {{ $ingredient->name }}
                                        - {{ $ingredient->branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                Jumlah
                            </label>

                            <input
                                type="number"
                                name="ingredients[${ingredientIndex}][quantity]"
                                class="form-control"
                                min="0.01"
                                step="0.01"
                                required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">
                                Satuan
                            </label>

                            <input
                                type="text"
                                class="form-control ingredient-unit"
                                readonly>
                        </div>

                        <div class="col-md-1">
                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                onclick="removeIngredient(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                    </div>
                </div>
            `;

            document
                .getElementById('ingredient-container')
                .insertAdjacentHTML('beforeend', html);

            ingredientIndex++;
        }

        function removeIngredient(button) {
            const rows = document.querySelectorAll('.ingredient-row');

            if (rows.length <= 1) {
                alert('Minimal harus ada 1 bahan.');
                return;
            }

            button.closest('.ingredient-row').remove();
        }

        function updateUnit(select) {
            let option = select.options[select.selectedIndex];

            let unit = option.getAttribute('data-unit') ?? '';

            select
                .closest('.ingredient-row')
                .querySelector('.ingredient-unit')
                .value = unit;
        }
    </script>

@endsection