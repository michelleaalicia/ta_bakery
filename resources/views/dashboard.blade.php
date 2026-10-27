@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Penjualan Hari Ini</div>
                    <h4 class="mb-0 fw-semibold">Rp {{ number_format($revenueToday, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Produksi Aktif</div>
                    <h4 class="mb-0 fw-semibold">{{ $activeProductionsCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Custom Order Aktif</div>
                    <h4 class="mb-0 fw-semibold">{{ $activeCustomOrdersCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Stok Bahan Baku Menipis</div>
                    <h4 class="mb-0 fw-semibold">{{ $lowStockCount }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Produksi Terbaru</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk / Varian</th>
                            <th>Cabang</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentProductions as $production)
                            @php
                                $isCustom = $production->order_type === 'custom';
                                $productLabel = $isCustom
                                    ? ($production->customOrder?->product_name ?? 'Pesanan Custom')
                                    : trim(($production->productVariant?->product?->name ?? 'Produk') . ' / ' . ($production->productVariant?->name ?? '-'));
                                $productionStatus = match ($production->status) {
                                    'planned' => 'Direncanakan',
                                    'in_progress' => 'Berlangsung',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                    default => ucfirst((string) $production->status),
                                };
                            @endphp
                            <tr>
                                <td>
                                    {{ $productLabel }}
                                    @if ($isCustom)
                                        <span class="badge bg-secondary ms-1">Custom</span>
                                    @endif
                                </td>
                                <td>{{ $production->branch?->name ?? '-' }}</td>
                                <td>{{ (float) $production->quantity == (int) $production->quantity ? (int) $production->quantity : rtrim(rtrim(number_format((float) $production->quantity, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="text-nowrap">{{ $production->production_date ? \Illuminate\Support\Carbon::parse($production->production_date)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    <span class="badge {{ match ($production->status) {
                                        'completed' => 'bg-success',
                                        'in_progress' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    } }}">{{ $productionStatus }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('production_orders.show', $production) }}" class="btn btn-outline-dark btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada produksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Custom Order Aktif</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Produk Custom</th>
                            <th>Cabang</th>
                            <th>Tanggal Dibutuhkan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeCustomOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->product_name }}</td>
                                <td>{{ $order->branch?->name ?? '-' }}</td>
                                <td class="text-nowrap">{{ $order->required_date ? \Illuminate\Support\Carbon::parse($order->required_date)->format('d/m/Y') : '-' }}</td>
                                <td><span class="badge bg-primary">Dikonfirmasi</span></td>
                                <td><a href="{{ route('custom_orders.show', $order) }}" class="btn btn-outline-dark btn-sm">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada custom order aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Stok Bahan Baku Menipis</div>
        <div class="card-body p-0">
            <div class="dashboard-table-scroll table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bahan Baku</th>
                            <th>Cabang</th>
                            <th>Stok</th>
                            <th>Minimum Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockIngredients as $ingredient)
                            @php
                                $displayQuantity = fn ($number) => rtrim(rtrim(number_format((float) $number, 3, ',', '.'), '0'), ',');
                            @endphp
                            <tr>
                                <td>{{ $ingredient->name }}</td>
                                <td>{{ $ingredient->branch?->name ?? '-' }}</td>
                                <td>{{ $displayQuantity($ingredient->stock) }} {{ $ingredient->unit }}</td>
                                <td>{{ $displayQuantity($ingredient->min_stock) }} {{ $ingredient->unit }}</td>
                                <td><span class="badge bg-warning text-dark">Menipis</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada stok yang menipis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .dashboard-table-scroll {
            max-height: 295px;
            overflow-y: auto;
        }
        .dashboard-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #f8f9fa;
            white-space: nowrap;
        }
        .dashboard-table-scroll td,
        .dashboard-table-scroll th {
            vertical-align: middle;
            text-align: left;
        }
    </style>
@endsection
