@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')

    <div class="card">
        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">

                {{-- Nama Role --}}
                <div class="mb-4">
                    <label for="name" class="form-label">
                        Nama Role
                    </label>

                    <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}"
                        class="form-control @error('name') is-invalid @enderror" placeholder="Masukkan nama role" required>

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Hak Akses --}}
                <div class="mb-3">
                    <label class="form-label">
                        Hak Akses
                    </label>

                    @foreach ($modules as $module)
                        <div class="form-check mb-2">

                            <input type="checkbox" name="modules[]" value="{{ $module->id }}" id="module_{{ $module->id }}"
                                class="form-check-input" {{ $role->modules->contains($module->id) ? 'checked' : '' }}>

                            <label for="module_{{ $module->id }}" class="form-check-label">
                                {{ $module->name }}
                            </label>

                        </div>
                    @endforeach

                    @error('modules')
                        <div class="text-danger mt-2">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

            <div class="card-footer">

                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan
                </button>

            </div>

        </form>

    </div>

@endsection