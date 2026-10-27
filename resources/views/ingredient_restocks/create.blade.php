@extends('layouts.admin')

@section('title', 'Restock Bahan Baku')
@section('page-title', 'Restock Bahan Baku')

@section('content')

    <div class="card">
        <form method="POST" action="{{ route('ingredient-restocks.store') }}">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label for="branch_id" class="form-label">Cabang</label>

                    @if (auth()->user()->role?->name !== 'Owner')
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endif
                    <select name="branch_id" id="branch_id" class="form-select" required {{ auth()->user()->role?->name !== 'Owner' ? 'disabled' : '' }}>
                        <option value="">Pilih Cabang</option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((auth()->user()->role?->name !== 'Owner' ? auth()->user()->branch_id : old('branch_id')) == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('branch_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="ingredient_id" class="form-label">Bahan Baku</label>

                    <select name="ingredient_id" id="ingredient_id" class="form-select" required disabled>
                        <option value="">Pilih Cabang Terlebih Dahulu</option>

                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" data-branch-id="{{ $ingredient->branch_id }}">
                                {{ $ingredient->name }}
                                | Stok: {{ rtrim(rtrim(number_format($ingredient->stock, 2, ',', '.'), '0'), ',') }}
                                {{ $ingredient->unit }}
                            </option>
                        @endforeach
                    </select>

                    @error('ingredient_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Jumlah Restock</label>

                    <input type="number" name="quantity" id="quantity" class="form-control" min="0.01" step="0.01"
                        value="{{ old('quantity') }}" placeholder="Masukkan jumlah bahan" required>

                    @error('quantity')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="unit_price" class="form-label">Harga Beli / Satuan</label>

                    <input type="number" name="unit_price" id="unit_price" class="form-control" min="0" step="0.01"
                        value="{{ old('unit_price') }}" placeholder="Masukkan harga beli per satuan" required>

                    @error('unit_price')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="restock_date" class="form-label">Tanggal Restock</label>

                    <select name="restock_date" id="restock_date" class="form-select" required>
                        <option value="{{ now()->toDateString() }}" @selected(old('restock_date', now()->toDateString()) === now()->toDateString())>
                            Hari Ini ({{ now()->translatedFormat('d F Y') }})
                        </option>

                        <option value="{{ now()->subDay()->toDateString() }}"
                            @selected(old('restock_date') === now()->subDay()->toDateString())>
                            Kemarin ({{ now()->subDay()->translatedFormat('d F Y') }})
                        </option>
                    </select>

                    @error('restock_date')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">
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
            const branchSelect = document.getElementById('branch_id');
            const ingredientSelect = document.getElementById('ingredient_id');

            const ingredients = Array.from(
                ingredientSelect.querySelectorAll('option[data-branch-id]')
            ).map(option => option.cloneNode(true));

            const oldIngredientId = @json(old('ingredient_id'));
            const oldBranchId = @json(auth()->user()->role?->name !== 'Owner' ? auth()->user()->branch_id : old('branch_id'));

            function updateIngredients(selectedId = '') {
                const branchId = branchSelect.value;

                ingredientSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = branchId
                    ? 'Pilih Bahan Baku'
                    : 'Pilih Cabang Terlebih Dahulu';

                ingredientSelect.appendChild(placeholder);
                ingredientSelect.disabled = !branchId;

                ingredients.forEach(function (option) {
                    if (option.dataset.branchId === branchId) {
                        ingredientSelect.appendChild(option.cloneNode(true));
                    }
                });

                ingredientSelect.value = String(selectedId);

                if (ingredientSelect.selectedIndex === -1) {
                    ingredientSelect.value = '';
                }
            }

            branchSelect.addEventListener('change', function () {
                updateIngredients();
            });

            if (oldBranchId) {
                branchSelect.value = String(oldBranchId);
            } else if (oldIngredientId) {
                const oldIngredient = ingredients.find(option =>
                    option.value === String(oldIngredientId)
                );

                if (oldIngredient) {
                    branchSelect.value = oldIngredient.dataset.branchId;
                }
            }

            updateIngredients(oldIngredientId);
        });
    </script>

@endsection