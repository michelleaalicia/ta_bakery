@extends('layouts.admin')

@section('title', 'Tambah Role')
@section('page-title', 'Tambah Role')

@section('content')

    <div class="card">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Nama Role</label>

                    <input type="text" name="name" value="{{ old('name') }}" class="form-control"
                        placeholder="Contoh: Kasir" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Permission Module</label>

                    @foreach ($modules as $module)
                        <div class="form-check mb-2">

                            <input type="checkbox" name="modules[]" value="{{ $module->id }}" class="form-check-input"
                                id="module{{ $module->id }}">

                            <label class="form-check-label" for="module{{ $module->id }}">
                                {{ $module->name }}
                            </label>

                        </div>
                    @endforeach
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