@extends('layouts.admin')

@section('title', 'Detail Cabang')
@section('page-title', 'Detail Cabang')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Detail Cabang</h3>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <strong>Nama Cabang</strong>
                <p>{{ $branch->name }}</p>
            </div>

            <div class="mb-3">
                <strong>Alamat</strong>
                <p>{{ $branch->address }}</p>
            </div>

            <div class="mb-3">
                <strong>No. Telepon</strong>
                <p>{{ $branch->phone_number }}</p>
            </div>

        </div>

        <div class="card-footer">
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                Kembali
            </a>

            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning">
                Edit
            </a>
        </div>

    </div>

@endsection