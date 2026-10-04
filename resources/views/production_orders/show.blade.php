@extends('layouts.admin')

@section('title', 'Detail Produksi')
@section('page-title', 'Detail Produksi')

@section('content')

    <div class="card">

        <div class="card-body">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-start mb-4">

                <div>
                    <p class="text-muted mb-0" style="font-size: 13px;">
                        Informasi dan perhitungan biaya produksi.
                    </p>
                </div>

                <div>
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
                </div>

            </div>


            {{-- Informasi Produksi --}}
            <div class="border rounded p-3 mb-4">

                <h6 class="fw-semibold mb-3">
                    Informasi Produksi
                </h6>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Produk - Varian
                        </div>

                        <div class="fw-semibold">
                            {{ $productionOrder->productVariant->product->name }}
                            -
                            {{ $productionOrder->productVariant->name }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Cabang
                        </div>

                        <div>
                            {{ $productionOrder->branch->name }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Jumlah Produksi
                        </div>

                        <div>
                            {{ rtrim(rtrim(number_format($productionOrder->quantity, 2, ',', '.'), '0'), ',') }}
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Tanggal Produksi
                        </div>

                        <div>
                            {{ \Carbon\Carbon::parse($productionOrder->production_date)->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted" style="font-size: 13px;">
                            Biaya Overhead Produksi
                        </div>

                        <div>
                            Rp {{ number_format($productionOrder->bop_cost, 0, ',', '.') }}
                        </div>
                    </div>

                    @if ($productionOrder->notes)

                        <div class="col-md-6">
                            <div class="text-muted" style="font-size: 13px;">
                                Catatan
                            </div>

                            <div>
                                {{ $productionOrder->notes }}
                            </div>
                        </div>

                    @endif

                </div>

            </div>


            {{-- Bahan Baku --}}
            <div class="mb-4">

                <h6 class="fw-semibold mb-3">
                    Bahan Baku
                </h6>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Bahan</th>
                                <th>Jumlah Digunakan</th>
                                <th>Harga/Satuan</th>
                                <th>Total Biaya</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($productionOrder->ingredients as $ingredient)

                                @php
                                    $totalIngredientCost =
                                        $ingredient->pivot->quantity_used *
                                        $ingredient->pivot->unit_cost_used;
                                @endphp

                                <tr>

                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        {{ $ingredient->name }}
                                    </td>

                                    <td>
                                        {{ rtrim(rtrim(number_format($ingredient->pivot->quantity_used, 2, ',', '.'), '0'), ',') }}
                                        {{ $ingredient->unit }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($ingredient->pivot->unit_cost_used, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($totalIngredientCost, 0, ',', '.') }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada bahan baku.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Karyawan --}}
            <div class="mb-4">

                <h6 class="fw-semibold mb-3">
                    Karyawan Produksi
                </h6>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Karyawan</th>
                                <th>Pekerjaan</th>
                                <th>Jam Kerja</th>
                                <th>Tarif/Jam</th>
                                <th>Total Upah</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($productionOrder->users as $user)

                                @php
                                    $totalLabor =
                                        $user->pivot->labor_hours *
                                        $user->wage_rate_per_hour;
                                @endphp

                                <tr>

                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        {{ $user->name }}
                                    </td>

                                    <td>
                                        {{ $user->pivot->job_description }}
                                    </td>

                                    <td>
                                        {{ rtrim(rtrim(number_format($user->pivot->labor_hours, 2, ',', '.'), '0'), ',') }}
                                        jam
                                    </td>

                                    <td>
                                        Rp {{ number_format($user->wage_rate_per_hour, 0, ',', '.') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($totalLabor, 0, ',', '.') }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Belum ada karyawan.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Perhitungan Biaya --}}
            <div class="border rounded p-3 mb-4">

                <h6 class="fw-semibold mb-3">
                    Perhitungan Biaya
                </h6>

                <div class="row">

                    <div class="col-md-8">

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Biaya Bahan Baku</span>
                            <span>
                                Rp {{ number_format($materialCost, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Biaya Tenaga Kerja</span>
                            <span>
                                Rp {{ number_format($laborCost, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Biaya Overhead Produksi</span>
                            <span>
                                Rp {{ number_format($productionOrder->bop_cost, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-3 fw-semibold">
                            <span>Total Biaya Produksi</span>
                            <span>
                                Rp {{ number_format($totalCost, 0, ',', '.') }}
                            </span>
                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="border rounded p-3 text-center h-100">

                            <div class="text-muted mb-2" style="font-size: 13px;">
                                HPP per Unit
                            </div>

                            <div class="fs-4 fw-semibold">
                                Rp {{ number_format($hppPerUnit, 0, ',', '.') }}
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span>Profit yang Diinginkan</span>
                                <span>
                                    {{ rtrim(rtrim(number_format($productionOrder->profit_percentage, 2, ',', '.'), '0'), ',') }}%
                                </span>
                            </div>

                            <div class="d-flex justify-content-between py-3 fw-semibold">
                                <span>Harga Jual per Unit</span>
                                <span>
                                    Rp {{ number_format($productionOrder->selling_price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            {{-- Tombol --}}
            <div class="d-flex gap-2">

                <a href="{{ route('production_orders.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                @if ($productionOrder->status === 'planned')
                    <form action="{{ route('production_orders.update-status', $productionOrder) }}" method="POST"
                        class="d-inline">

                        @csrf
                        @method('PUT')

                        <input type="hidden" name="status" value="in_progress">

                        <button type="submit" class="btn btn-dark">
                            Mulai Produksi
                        </button>

                    </form>

                @elseif ($productionOrder->status === 'in_progress')

                    <form action="{{ route('production_orders.update-status', $productionOrder) }}" method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Yakin produksi ini sudah selesai? Stok bahan baku akan dikurangi.')">

                        @csrf
                        @method('PUT')

                        <input type="hidden" name="status" value="completed">

                        <button type="submit" class="btn btn-success">
                            Selesaikan Produksi
                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>

@endsection