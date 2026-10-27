@extends('layouts.admin')

@section('title', 'Detail Penjualan')
@section('page-title', 'Detail Penjualan POS')

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">POS-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</h5>
            <span
                class="badge {{ $sale->payment_status === 'paid' ? 'bg-success' : ($sale->payment_status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                {{ match ($sale->payment_status) { 'paid' => 'Lunas', 'pending' => 'Menunggu QRIS', 'cancelled' => 'Dibatalkan', default => ucfirst($sale->payment_status)} }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="text-muted small">Bakery</div><strong>{{ $sale->tenant?->name ?? '-' }}</strong>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Cabang</div><strong>{{ $sale->branch?->name ?? 'Pusat' }}</strong>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Tanggal Transaksi</div>
                    <strong>{{ $sale->sale_date?->format('d/m/Y') ?? '-' }}</strong>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Metode Pembayaran</div>
                    <strong>{{ strtoupper($sale->payment_method) }}</strong>
                </div>
                @if ($sale->payment_method === 'cash')
                    <div class="col-md-4">
                        <div class="text-muted small">Tunai Diterima</div><strong>Rp
                            {{ number_format($sale->cash_received ?? 0, 0, ',', '.') }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Kembalian</div><strong>Rp
                            {{ number_format($sale->change_amount ?? 0, 0, ',', '.') }}</strong>
                    </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->details as $detail)
                            <tr>
                                <td>{{ $detail->productVariant?->product?->name ?? 'Produk tidak tersedia' }}</td>
                                <td>{{ $detail->productVariant?->name ?? '-' }}</td>
                                <td class="text-end">{{ (float) $detail->quantity }}</td>
                                <td class="text-end">Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">Rp
                                    {{ number_format($detail->quantity * $detail->unit_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">

                <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                @if ($sale->payment_status === 'paid')
                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" rel="noopener" class="btn btn-dark">
                        Cetak Nota PDF
                    </a>
                @endif

                @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending')
                    <button type="button" id="confirmQrisButton" class="btn btn-success">
                        Konfirmasi Pembayaran QRIS
                    </button>

                    <button type="button" id="cancelQrisButton" class="btn btn-outline-danger">
                        Batalkan Transaksi
                    </button>
                @endif

            </div>

            @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending')
                <div id="qrisActionMessage" class="alert d-none mt-3" role="alert"></div>
            @endif
        </div>
    </div>
@endsection
@push('scripts')
    @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const confirmButton = document.getElementById('confirmQrisButton');
                const cancelButton = document.getElementById('cancelQrisButton');
                const message = document.getElementById('qrisActionMessage');

                const confirmUrl = @json(route('pos.confirm-payment', $sale));
                const cancelUrl = @json(route('pos.cancel-payment', $sale));
                const csrfToken = @json(csrf_token());

                async function processPayment(url, question) {
                    if (!confirm(question)) return;

                    confirmButton.disabled = true;
                    cancelButton.disabled = true;

                    message.className = 'alert d-none mt-3';
                    message.textContent = '';

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            const firstError = result.errors
                                ? Object.values(result.errors).flat()[0]
                                : null;

                            throw new Error(
                                firstError || result.message || 'Proses pembayaran gagal.'
                            );
                        }

                        window.location.reload();
                    } catch (error) {
                        message.textContent = error.message;
                        message.className = 'alert alert-danger mt-3';

                        confirmButton.disabled = false;
                        cancelButton.disabled = false;
                    }
                }

                confirmButton.addEventListener('click', function () {
                    processPayment(
                        confirmUrl,
                        'Pastikan pembayaran QRIS sudah benar-benar diterima. Konfirmasi sekarang?'
                    );
                });

                cancelButton.addEventListener('click', function () {
                    processPayment(
                        cancelUrl,
                        'Yakin ingin membatalkan transaksi QRIS ini?'
                    );
                });
            });
        </script>
    @endif
@endpush