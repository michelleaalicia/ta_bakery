@extends('layouts.admin')

@section('title', 'Tambah Bahan Baku')
@section('page-title', 'Tambah Bahan Baku')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('ingredients.store') }}">
            @csrf

            <div class="card-body">


                <div class="mb-3">
                    <label for="branch_id" class="form-label">Cabang</label>

                    @if (auth()->user()->role?->name === 'Owner')
                        {{-- Owner bisa memilih cabang --}}
                        <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                            required>
                            <option value="">Pilih Cabang</option>

                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        {{-- Karyawan menggunakan cabang akun --}}
                        <input type="text" class="form-control"
                            value="{{ auth()->user()->branch?->name ?? 'Cabang belum ditentukan' }}" readonly>

                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endif

                    @error('branch_id')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
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