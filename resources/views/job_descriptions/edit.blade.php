@extends('layouts.admin')

@section('title', 'Edit Job Description')
@section('page-title', 'Edit Job Description')

@section('content')

    <div class="card">

        <form action="{{ route('job-descriptions.update', $jobDescription) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama Job Description
                    </label>

                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $jobDescription->name) }}" placeholder="Contoh: Membuat Adonan"
                        maxlength="255" required>

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
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

@endsection