@if ($customOrder->status === 'delivered' && $customOrder->delivered_at)
    <div class="alert alert-success mb-3">
        Pesanan telah diserahkan pada
        <strong>{{ $customOrder->delivered_at->format('d/m/Y H:i') }}</strong>.
    </div>
@elseif (
    $customOrder->status === 'confirmed' &&
    $customOrder->productionOrder?->status === 'completed'
)
    @php
        $deliveryPaid = (float) $customOrder->payments->sum('amount');
        $deliveryTotal = (float) $customOrder->selling_price;
        $deliveryRemaining = max(0, round($deliveryTotal - $deliveryPaid, 2));
        $canDeliver = in_array(auth()->user()->role?->name, ['Owner', 'Kasir'], true);
    @endphp

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Penyerahan Pesanan</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span>Status Produksi</span>
                <strong>Selesai</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span>Sisa Tagihan</span>
                <strong>Rp {{ number_format($deliveryRemaining, 0, ',', '.') }}</strong>
            </div>

            @if ($deliveryRemaining > 0)
                <div class="alert alert-warning mb-0">
                    Pelunasan belum selesai. Pesanan belum dapat diserahkan.
                    <a href="{{ route('custom_order_payments.show', $customOrder) }}">Buka Pembayaran</a>
                </div>
            @elseif ($canDeliver)
                <form action="{{ route('custom_orders.deliver', $customOrder) }}" method="POST"
                    onsubmit="return confirm('Pastikan pesanan sudah diterima pelanggan. Tandai sebagai Diserahkan?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Serahkan Pesanan</button>
                </form>
            @else
                <span class="text-muted">Penyerahan dicatat oleh Owner atau Kasir.</span>
            @endif
        </div>
    </div>
@endif
