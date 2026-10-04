@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@section('content')

    <div class="card">
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>

                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

@endsection