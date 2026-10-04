@extends('layouts.admin')

@section('title', 'Produksi')
@section('page-title', 'Produksi')

@section('content')

    <div class="card">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('production_orders.create') }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>
                    Tambah Produksi
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
                <table class="table table-bordered align-middle mb-0">

                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Produk - Varian</th>
                            <th>Cabang</th>
                            <th style="width: 120px;">Jumlah</th>
                            <th style="width: 150px;">Tanggal Produksi</th>
                            <th style="width: 130px;">Status</th>
                            <th style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($productionOrders as $productionOrder)

                            <tr>

                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $productionOrder->productVariant->product->name }}
                                    </div>

                                    <div class="text-muted" style="font-size: 13px;">
                                        {{ $productionOrder->productVariant->name }}
                                    </div>
                                </td>

                                <td>
                                    {{ $productionOrder->branch->name }}
                                </td>

                                <td>
                                    {{ rtrim(rtrim(number_format($productionOrder->quantity, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($productionOrder->production_date)->format('d/m/Y') }}
                                </td>

                                <td>
                                    @if ($productionOrder->status === 'planned')
                                        <span class="badge bg-secondary">
                                            Direncanakan
                                        </span>
                                    @elseif ($productionOrder->status === 'in_progress')
                                        <span class="badge bg-warning text-dark">
                                            Berlangsung
                                        </span>
                                    @elseif ($productionOrder->status === 'completed')
                                        <span class="badge bg-success">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            Dibatalkan
                                        </span>
                                    @endif
                                </td>

                                <td>

                                    <a href="{{ route('production_orders.show', $productionOrder) }}"
                                        class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    <a href="{{ route('production_orders.edit', $productionOrder) }}"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('production_orders.destroy', $productionOrder) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produksi ini?')">

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
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada data produksi.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </div>

    </div>

@endsection