@extends('layouts.admin')

@section('title', 'Detail Kategori')
@section('page-title', 'Detail Kategori')

@section('content')

    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Detail Kategori</h3>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <strong>Nama Kategori</strong>
                <p>{{ $category->name }}</p>
            </div>

        </div>

        <div class="card-footer">

            <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                Kembali
            </a>

            <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning">
                Edit
            </a>

        </div>

    </div>

@endsection