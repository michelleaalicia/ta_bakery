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
                <a href="{{ route('sales.index') }}" class="btn btn-secondary">Kembali</a>
                @if ($sale->payment_status === 'paid')
                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" rel="noopener" class="btn btn-dark">Cetak Nota
                        PDF</a>
                @endif
                @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending' && $sale->midtrans_order_id)
                    <button type="button" id="checkMidtransButton" class="btn btn-primary">Cek Status Midtrans</button>
                    <button type="button" id="cancelMidtransButton" class="btn btn-outline-danger">Batalkan via
                        Midtrans</button>
                @endif
            </div>
            @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending')
                @if (!$sale->midtrans_order_id)
                    <div class="alert alert-warning mt-3 mb-2">
                        Transaksi QRIS ini belum menyimpan Order ID Midtrans. Salin Order ID dari detail transaksi di dashboard
                        Midtrans Sandbox.
                    </div>
                    <div class="input-group mt-2" style="max-width: 680px;">
                        <input id="legacyOrderIdInput" type="text" class="form-control"
                            placeholder="Contoh: SIMB-{{ $sale->id }}-xxxxxxxxxxxx" autocomplete="off">
                        <button id="attachMidtransButton" class="btn btn-dark" type="button">Cek Status</button>
                    </div>
                @endif
                <div id="midtransActionMessage" class="alert d-none mt-3" role="alert"></div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @if ($sale->payment_method === 'qris' && $sale->payment_status === 'pending')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const statusButton = document.getElementById('checkMidtransButton');
                const cancelButton = document.getElementById('cancelMidtransButton');
                const attachButton = document.getElementById('attachMidtransButton');
                const legacyInput = document.getElementById('legacyOrderIdInput');
                const message = document.getElementById('midtransActionMessage');
                const csrfToken = @json(csrf_token());
                const statusUrl = @json(route('pos.qris-status', $sale));
                const cancelUrl = @json(route('pos.midtrans-cancel', $sale));
                const attachUrl = @json(route('pos.attach-midtrans-order', $sale));
                const controls = [statusButton, cancelButton, attachButton, legacyInput].filter(Boolean);

                async function run(url, method = 'GET', body = null) {
                    controls.forEach(el => el.disabled = true);
                    message.className = 'alert d-none mt-3';
                    message.textContent = '';
                    try {
                        const response = await fetch(url, {
                            method,
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                ...(body ? { 'Content-Type': 'application/json' } : {}),
                            },
                            ...(body ? { body: JSON.stringify(body) } : {}),
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Proses gagal.');
                        }
                        if (data.payment_status && data.payment_status !== 'pending') {
                            window.location.reload();
                            return;
                        }
                        message.textContent = data.message || 'Status masih Menunggu. Belum ada pembayaran berhasil.';
                        message.className = 'alert alert-info mt-3';
                    } catch (error) {
                        message.textContent = error.message || 'Tidak dapat memeriksa Midtrans.';
                        message.className = 'alert alert-danger mt-3';
                    } finally {
                        controls.forEach(el => el.disabled = false);
                    }
                }
                statusButton?.addEventListener('click', () => run(statusUrl));
                cancelButton?.addEventListener('click', () => {
                    if (confirm('Batalkan transaksi QRIS ini melalui Midtrans? Pembayaran yang sudah berhasil tidak dapat dibatalkan lewat tombol ini.')) {
                        run(cancelUrl, 'POST');
                    }
                });
                attachButton?.addEventListener('click', () => {
                    const midtrans_order_id = legacyInput.value.trim();
                    if (!midtrans_order_id) {
                        message.textContent = 'Isi Order ID lengkap dari dashboard Midtrans.';
                        message.className = 'alert alert-warning mt-3';
                        return;
                    }
                    run(attachUrl, 'POST', { midtrans_order_id });
                });
            });
        </script>
    @endif
@endpush