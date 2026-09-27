@extends('layouts.admin')

@section('title', 'Ubah Bahan Baku')
@section('page-title', 'Ubah Bahan Baku')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Ubah Bahan Baku</h3>
        </div>

        <form method="POST" action="{{ route('ingredients.update', $ingredient->id) }}">

            @csrf
            @method('PUT')

            <div class="card-body">

                {{-- Cabang --}}
                <div class="mb-3">
                    <label class="form-label">Cabang</label>

                    <select name="branch_id" class="form-select" required>

                        @foreach ($branches as $branch)

                            <option value="{{ $branch->id }}" {{ $ingredient->branch_id == $branch->id ? 'selected' : '' }}>

                                {{ $branch->name }}

                            </option>

                        @endforeach

                    </select>
                </div>


                {{-- Nama Bahan --}}
                <div class="mb-3">
                    <label class="form-label">Nama Bahan</label>

                    <input type="text" name="name" value="{{ old('name', $ingredient->name) }}" class="form-control"
                        required>
                </div>


                {{-- Satuan --}}
                <div class="mb-3">
                    <label class="form-label">Satuan</label>

                    <input type="text" name="unit" value="{{ old('unit', $ingredient->unit) }}" class="form-control"
                        required>
                </div>


                {{-- Harga --}}
                <div class="mb-3">
                    <label class="form-label">Harga Satuan</label>

                    <input type="text" value="Rp {{ number_format($ingredient->unit_cost, 0, ',', '.') }}"
                        class="form-control" disabled>

                    <small class="text-muted">
                        Harga diubah melalui proses restock.
                    </small>
                </div>


                {{-- Stok --}}
                <div class="mb-3">
                    <label class="form-label">Stok Saat Ini</label>

                    <input type="text" value="{{ $ingredient->stock }} {{ $ingredient->unit }}" class="form-control"
                        disabled>

                    <small class="text-muted">
                        Stok diubah melalui proses restock atau produksi.
                    </small>
                </div>


                {{-- Minimum Stok --}}
                <div class="mb-3">
                    <label class="form-label">Minimum Stok</label>

                    <input type="number" name="min_stock" value="{{ old('min_stock', $ingredient->min_stock) }}"
                        class="form-control" min="0" step="0.01" required>
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