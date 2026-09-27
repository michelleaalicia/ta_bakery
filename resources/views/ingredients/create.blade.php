@extends('layouts.admin')

@section('title', 'Tambah Bahan Baku')
@section('page-title', 'Tambah Bahan Baku')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Tambah Bahan Baku</h3>
        </div>

        <form method="POST" action="{{ route('ingredients.store') }}">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Cabang</label>

                    <select name="branch_id" class="form-select" required>

                        <option value="">Pilih Cabang</option>

                        @foreach ($branches as $branch)

                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>

                                {{ $branch->name }}

                            </option>

                        @endforeach

                    </select>
                </div>


                <div class="mb-3">
                    <label class="form-label">Nama Bahan</label>

                    <input type="text" name="name" value="{{ old('name') }}" class="form-control"
                        placeholder="Nama bahan baku" required>
                </div>


                <div class="mb-3">
                    <label class="form-label">Satuan</label>

                    <input type="text" name="unit" value="{{ old('unit') }}" class="form-control"
                        placeholder="Contoh: kg, pcs, liter" required>
                </div>


                <div class="mb-3">
                    <label class="form-label">Harga Satuan</label>

                    <input type="number" name="unit_cost" value="{{ old('unit_cost', 0) }}" class="form-control" min="0"
                        required>
                </div>


                <div class="mb-3">
                    <label class="form-label">Stok Awal</label>

                    <input type="number" name="stock" value="{{ old('stock', 0) }}" class="form-control" min="0" step="0.01"
                        required>
                </div>


                <div class="mb-3">
                    <label class="form-label">Minimum Stok</label>

                    <input type="number" name="min_stock" value="{{ old('min_stock', 0) }}" class="form-control" min="0"
                        step="0.01" required>
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