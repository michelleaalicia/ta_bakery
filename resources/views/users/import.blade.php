@extends('layouts.admin')

@section('title', 'Import Pengguna')
@section('page-title', 'Import Pengguna')

@section('content')

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <p>
                Unduh template Excel terlebih dahulu, kemudian isi data pengguna
                sesuai dengan kolom yang tersedia.
            </p>

            <a href="{{ route('users.import.template') }}" class="btn btn-secondary btn-sm mb-3">
                Download Template Excel
            </a>

            <div class="table-responsive mb-3">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Kolom</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Nama</td>
                            <td>Wajib diisi.</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>Wajib diisi dan belum digunakan pengguna lain.</td>
                        </tr>
                        <tr>
                            <td>Role</td>
                            <td>Wajib diisi sesuai role yang tersedia. Role Owner tidak dapat diimpor.</td>
                        </tr>
                        <tr>
                            <td>Cabang (Opsional)</td>
                            <td>
                                Wajib diisi jika bakery memiliki cabang.
                                Jika bakery tidak memiliki cabang, kolom ini dikosongkan.
                            </td>
                        </tr>
                        <tr>
                            <td>Tarif Upah/Jam</td>
                            <td>Wajib diisi dengan angka, contoh: 25000.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info">
                Password dibuat otomatis dan status pengguna menjadi Aktif.
                Jangan mengubah nama header pada baris pertama template.
            </div>

            <form action="{{ route('users.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">File Pengguna</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                </div>

                <div class="mt-3">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit" class="btn btn-dark">
                        Import Pengguna
                    </button>
                </div>
            </form>

        </div>
    </div>

@endsection