@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('page-title', 'Tambah Pengguna')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="card-body">

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nama
                    </label>

                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror" placeholder="Masukkan nama pengguna"
                        required>

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
                        class="form-control @error('email') is-invalid @enderror" placeholder="Masukkan email pengguna"
                        required>

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Role --}}
                <div class="mb-3">
                    <label for="role_id" class="form-label">
                        Role
                    </label>

                    <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>

                        <option value="">Pilih role</option>

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

                        <option value="">Pilih cabang</option>

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

                {{-- Tarif Upah/Jam --}}
                <div class="mb-3">
                    <label for="wage_rate_per_hour" class="form-label">
                        Tarif Upah/Jam
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">Rp</span>

                        <input type="text" name="wage_rate_per_hour" id="wage_rate_per_hour"
                            value="{{ old('wage_rate_per_hour') }}"
                            class="form-control @error('wage_rate_per_hour') is-invalid @enderror"
                            placeholder="Masukkan tarif upah per jam" required>
                    </div>

                    @error('wage_rate_per_hour')
                        <div class="invalid-feedback d-block">
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

    <script>
        const wageInput = document.getElementById('wage_rate_per_hour');

        wageInput.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');

            if (value) {
                this.value = new Intl.NumberFormat('id-ID').format(value);
            }
        });
    </script>

@endsection