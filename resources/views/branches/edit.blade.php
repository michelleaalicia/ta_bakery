@extends('layouts.admin')

@section('title', 'Edit Cabang')
@section('page-title', 'Edit Cabang')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Edit Cabang</h3>
        </div>

        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Nama Cabang</label>
                    <input type="text" name="name" value="{{ old('name', $branch->name) }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3"
                        required>{{ old('address', $branch->address) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $branch->phone_number) }}"
                        class="form-control" required>
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

@endsection