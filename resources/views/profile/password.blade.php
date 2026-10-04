@extends('layouts.admin')

@section('title', 'Ubah Password')
@section('page-title', 'Ubah Password')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Ubah Password</h3>
        </div>

        @if (session('success'))
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password.update') }}">
            @csrf
            @method('PUT')

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Password Saat Ini</label>

                    <input type="password" name="current_password"
                        class="form-control @error('current_password') is-invalid @enderror" required>

                    @error('current_password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>

                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        required>

                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>

                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

            </div>

            <div class="card-footer">

                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>

            </div>

        </form>

    </div>

@endsection