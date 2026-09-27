@extends('layouts.admin')

@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Tambah Kategori</h3>
        </div>

        <form method="POST" action="{{ route('categories.store') }}">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>

                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>
            </div>

        </form>

    </div>

@endsection