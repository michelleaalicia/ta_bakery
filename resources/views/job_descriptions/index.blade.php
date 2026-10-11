@extends('layouts.admin')

@section('title', 'Job Description')
@section('page-title', 'Job Description')

@section('content')

    <div class="card">

        <div class="card-body">

            <div class="mb-3">
                <a href="{{ route('job-descriptions.create') }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Job Description
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="60">No.</th>
                            <th>Nama Job Description</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($jobDescriptions as $jobDescription)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $jobDescription->name }}</td>

                                <td>
                                    <a href="{{ route('job-descriptions.edit', $jobDescription) }}"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('job-descriptions.destroy', $jobDescription) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus job description ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">
                                    Belum ada job description.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>

@endsection