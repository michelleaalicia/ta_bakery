@extends('layouts.admin')

@section('title', 'Tambah Cabang')
@section('page-title', 'Tambah Cabang')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('branches.store') }}">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Nama Cabang</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control"
                        placeholder="Masukkan nama cabang" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat cabang"
                        required>{{ old('address') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control"
                        placeholder="Contoh: 081234567890" required>
                </div>

            </div>

            <div class="card-footer">

                <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>

            </div>

        </form>

    </div>

@endsection