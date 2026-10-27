@extends('layouts.admin')

@section('title', 'Profil')
@section('page-title', 'Profil')

@section('content')
    @php
        $profileUser = auth()->user()->loadMissing(['role', 'tenant']);
        $isOwner = $profileUser->role?->name === 'Owner';
        $bakery = $profileUser->tenant;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 align-items-start">
        {{-- Kiri: profil pengguna dan password --}}
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Profil Pengguna</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-body-secondary"
                            style="width:52px;height:52px;flex-shrink:0;">
                            <i class="bi bi-person fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $profileUser->name }}</div>
                            <span class="text-muted small">{{ $profileUser->role?->name ?? '-' }}</span>
                        </div>
                    </div>

                    @if ($isOwner)
                        <form action="{{ route('profile.name.update') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="profileName" class="form-label">Nama Lengkap</label>
                                <input type="text" id="profileName" name="profile_name" class="form-control"
                                    value="{{ old('profile_name', $profileUser->name) }}" maxlength="255" required>
                            </div>

                            <div class="mb-3">
                                <label for="profileEmail" class="form-label">Email</label>
                                <input type="email" id="profileEmail" class="form-control profile-readonly"
                                    value="{{ $profileUser->email }}" readonly>
                            </div>

                            <button type="submit" class="btn btn-dark btn-sm">
                                Simpan Nama
                            </button>
                        </form>
                    @else
                        <div class="mb-3">
                            <label for="profileName" class="form-label">Nama Lengkap</label>
                            <input type="text" id="profileName" class="form-control profile-readonly"
                                value="{{ $profileUser->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="profileEmail" class="form-label">Email</label>
                            <input type="email" id="profileEmail" class="form-control profile-readonly"
                                value="{{ $profileUser->email }}" readonly>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Ubah Password</div>
                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Password Saat Ini</label>
                            <input type="password" id="currentPassword" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                autocomplete="current-password" required>
                            @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password Baru</label>
                            <input type="password" id="newPassword" name="password"
                                class="form-control @error('password') is-invalid @enderror" autocomplete="new-password"
                                minlength="8" required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" id="confirmPassword" name="password_confirmation" class="form-control"
                                autocomplete="new-password" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Kanan: informasi bakery --}}
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Profil Bakery</div>
                <div class="card-body">
                    @if ($isOwner)
                        <form method="POST" action="{{ route('profile.bakery.update') }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="bakeryName" class="form-label">Nama Bakery</label>
                                <input type="text" id="bakeryName" name="bakery_name" class="form-control"
                                    value="{{ old('bakery_name', $bakery?->name) }}" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label for="bakeryPhone" class="form-label">Nomor Telepon</label>
                                <input type="text" id="bakeryPhone" name="phone_number" class="form-control"
                                    value="{{ old('phone_number', $bakery?->phone_number) }}" maxlength="30">
                            </div>
                            <div class="mb-3">
                                <label for="bakeryAddress" class="form-label">Alamat Bakery</label>
                                <textarea id="bakeryAddress" name="address" rows="3" class="form-control"
                                    maxlength="1000">{{ old('address', $bakery?->address) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm">Simpan Informasi Bakery</button>
                        </form>
                    @else
                        <div class="mb-3">
                            <label for="bakeryName" class="form-label">Nama Bakery</label>
                            <input type="text" id="bakeryName" class="form-control" value="{{ $bakery?->name ?? '-' }}"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label for="bakeryPhone" class="form-label">Nomor Telepon</label>
                            <input type="text" id="bakeryPhone" class="form-control" value="{{ $bakery?->phone_number ?? '-' }}"
                                readonly>
                        </div>
                        <div>
                            <label for="bakeryAddress" class="form-label">Alamat Bakery</label>
                            <textarea id="bakeryAddress" rows="3" class="form-control"
                                readonly>{{ $bakery?->address ?? '-' }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <style>
        .profile-readonly {
            background-color: #f1f3f5 !important;
            color: #6c757d !important;
            cursor: default;
        }
    </style>
@endsection