@extends('layouts.admin')

@section('title', 'Tambah Job Description')
@section('page-title', 'Tambah Job Description')

@section('content')

    <div class="card">

        <form action="{{ route('job-descriptions.store') }}" method="POST">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Job Description
                    </label>

                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="Contoh: Membuat Adonan" maxlength="255" required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('job-descriptions.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>
            </div>

        </form>

    </div>

@endsection