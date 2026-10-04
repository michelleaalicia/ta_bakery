@extends('layouts.admin')

@section('title', 'Import Pengguna')

@section('page-title', 'Import Pengguna')

@section('content')

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <div class="mb-3">
                Format file: CSV atau XLSX.
                <p>Data yang diperlukan: Nama, Email, Role, Cabang, Tarif Upah/Jam.</p>

                <p class="mb-2 mt-2">
                    Contoh data:
                </p>

                <table class="table table-bordered table-sm mb-2">
                    <tbody>
                        <tr>
                            <td>Andi</td>
                            <td>andi@gmail.com</td>
                            <td>Kasir</td>
                            <td>Cabang Wiyung</td>
                            <td>25000</td>
                        </tr>
                        <tr>
                            <td>Budi</td>
                            <td>budi@gmail.com</td>
                            <td>Produksi</td>
                            <td>Cabang Wiyung</td>
                            <td>30000</td>
                        </tr>
                    </tbody>
                </table>

                <small>
                    Role dan Cabang harus sesuai dengan data yang tersedia di sistem.
                    Tarif Upah/Jam diisi dalam angka, contoh: 25000.
                    Password dibuat otomatis dan status pengguna otomatis Aktif.
                </small>
            </div>

            <form action="{{ route('users.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">File Pengguna</label>

                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required>
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