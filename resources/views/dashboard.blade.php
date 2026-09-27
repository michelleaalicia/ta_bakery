@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')

    <div class="card">
        <div class="card-body">
            Selamat datang, {{ auth()->user()->name }}!
        </div>
    </div>

@endsection