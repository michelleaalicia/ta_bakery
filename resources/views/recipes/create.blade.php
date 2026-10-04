@extends('layouts.admin')

@section('title', 'Tambah Resep')
@section('page-title', 'Tambah Resep')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('recipes.store') }}">

            @csrf

            <div class="card-body">

                {{-- Produk - Varian --}}
                <div class="mb-3">

                    <label class="form-label">
                        Produk - Varian
                    </label>

                    <select name="product_variant_id" class="form-select" required>

                        <option value="">
                            Pilih Produk - Varian
                        </option>

                        @foreach ($variants as $variant)

                            <option value="{{ $variant->id }}">

                                {{ $variant->product->name }}
                                /
                                {{ $variant->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Hasil Resep --}}
                <div class="mb-3">

                    <label class="form-label">
                        Hasil Resep
                    </label>

                    <div class="row">

                        <div class="col-md-6">
                            <input type="number" name="quantity" class="form-control" min="0.01" step="0.01"
                                placeholder="Contoh: 20" required>
                        </div>

                        <div class="col-md-6">

                            <select name="unit" class="form-select" required>

                                <option value="">
                                    Pilih Satuan
                                </option>

                                <option value="pcs">pcs</option>
                                <option value="loyang">loyang</option>
                                <option value="bungkus">bungkus</option>
                                <option value="box">box</option>
                                <option value="toples">toples</option>
                                <option value="kg">kg</option>
                                <option value="gram">gram</option>
                                <option value="liter">liter</option>
                                <option value="botol">botol</option>

                            </select>

                        </div>

                    </div>

                    <small class="text-muted">
                        Jumlah produk yang dihasilkan dari satu kali resep.
                    </small>

                </div>

                {{-- Langkah Pembuatan --}}
                <div class="mb-3">
                    <label class="form-label">
                        Langkah Pembuatan
                    </label>

                    <textarea name="steps" class="form-control" rows="3" placeholder="Tulis langkah-langkah pembuatan..."
                        required></textarea>
                </div>

                {{-- Bahan --}}
                <div class="d-flex justify-content-between align-items-center mb-2">

                    <label class="form-label mb-0">
                        Bahan Baku
                    </label>

                    <button type="button" class="btn btn-dark btn-sm" onclick="addIngredient()">

                        <i class="bi bi-plus"></i>
                        Tambah Bahan

                    </button>

                </div>


                <div id="ingredient-container">

                    <div class="ingredient-row border rounded p-3 mb-2">

                        <div class="row align-items-end">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Bahan
                                </label>

                                <select name="ingredients[0][ingredient_id]" class="form-select ingredient-select"
                                    onchange="updateUnit(this)" required>

                                    <option value="">
                                        Pilih Bahan
                                    </option>

                                    @foreach ($ingredients as $ingredient)

                                        <option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unit }}">

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

                                <input type="number" name="ingredients[0][quantity]" class="form-control" min="0.01"
                                    step="0.01" required>

                            </div>


                            <div class="col-md-2">

                                <label class="form-label">
                                    Satuan
                                </label>

                                <input type="text" class="form-control ingredient-unit" style="background-color: #f1f3f5;"
                                    readonly>

                            </div>


                            <div class="col-md-1">

                                <button type="button" class="btn btn-outline-danger" onclick="removeIngredient(this)">

                                    <i class="bi bi-trash"></i>

                                </button>

                            </div>

                        </div>

                    </div>

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

        let ingredientIndex = 1;

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

                                                        <input type="number"
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

                                                        <input type="text"
                                                               class="form-control ingredient-unit"
                                                               readonly>

                                                    </div>


                                                    <div class="col-md-1">

                                                        <button type="button"
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
            button.closest('.ingredient-row').remove();
        }


        function updateUnit(select) {
            let option = select.options[select.selectedIndex];

            let unit = option.getAttribute('data-unit') ?? '';

            select.closest('.ingredient-row')
                .querySelector('.ingredient-unit')
                .value = unit;
        }

    </script>

@endsection