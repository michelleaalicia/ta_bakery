@extends('layouts.admin')

@section('title', 'Restock Bahan Baku')
@section('page-title', 'Restock Bahan Baku')

@section('content')

    <div class="card">
        <form method="POST" action="{{ route('ingredient-restocks.store') }}">
            @csrf

            <div class="card-body">

                {{-- Bahan Baku --}}
                <div class="mb-3">
                    <label class="form-label">Bahan Baku</label>

                    <select name="ingredient_id" class="form-select" required>
                        <option value="">Pilih Bahan Baku</option>

                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" {{ old('ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }}
                                | Stok: {{ rtrim(rtrim(number_format($ingredient->stock, 2, ',', '.'), '0'), ',') }}
                                {{ $ingredient->unit }}
                                @if ($ingredient->branch)
                                    | Cabang: {{ $ingredient->branch->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('ingredient_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Jumlah --}}
                <div class="mb-3">
                    <label class="form-label">Jumlah Restock</label>

                    <input type="number" name="quantity" class="form-control" min="0.01" step="0.01"
                        value="{{ old('quantity') }}" placeholder="Masukkan jumlah bahan" required>

                    @error('quantity')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Harga --}}
                <div class="mb-3">
                    <label class="form-label">Harga Beli / Satuan</label>

                    <input type="number" name="unit_price" class="form-control" min="0" step="0.01"
                        value="{{ old('unit_price') }}" placeholder="Masukkan harga beli per satuan" required>

                    @error('unit_price')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Restock</label>

                    <input type="date" name="restock_date" value="{{ old('restock_date', date('Y-m-d')) }}"
                        class="form-control" required>

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
@endsection