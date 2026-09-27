@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('page-title', 'Tambah Pengguna')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Tambah Pengguna</h3>
        </div>

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="card-body">

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama
                    </label>

                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror" required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror" required>

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror" required>

                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                {{-- Konfirmasi Password --}}
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">
                        Konfirmasi Password
                    </label>

                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                        required>
                </div>


                {{-- Role --}}
                <div class="mb-3">
                    <label for="role_id" class="form-label">
                        Role
                    </label>

                    <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                        <option value="">Pilih Role</option>

                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('role_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                {{-- Cabang --}}
                <div class="mb-3">
                    <label for="branch_id" class="form-label">
                        Cabang
                    </label>

                    <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                        required>
                        <option value="">Pilih Cabang</option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('branch_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                {{-- Status --}}
                <div class="mb-3">
                    <label for="status" class="form-label">
                        Status
                    </label>

                    <select name="status" id="status" class="form-select" required>
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                            Nonaktif
                        </option>
                    </select>
                </div>

            </div>


            <div class="card-footer">

                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>

            </div>

        </form>

    </div>

@endsection