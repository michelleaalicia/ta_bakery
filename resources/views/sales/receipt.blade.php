<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Penjualan {{ $sale->id }}</title>
    <style>
        @page { margin: 24px 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #202020; }
        .center { text-align: center; }
        .title { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 10px; margin-bottom: 12px; }
        .rule { border-top: 1px solid #333; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 2px; vertical-align: top; }
        th { border-bottom: 1px solid #444; text-align: left; }
        .right { text-align: right; }
        .footer { margin-top: 22px; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    <div class="center title">{{ $sale->tenant?->name ?? 'Bakery' }}</div>
    @if ($sale->branch)
        <div class="center subtitle">Cabang {{ $sale->branch->name }}</div>
    @endif
    <div class="rule"></div>
    <div class="center" style="font-weight: bold; font-size: 13px;">NOTA PENJUALAN</div>
    <table style="margin-top: 10px;">
        <tr><td>No. Transaksi</td><td class="right">POS-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td>Tanggal</td><td class="right">{{ $sale->sale_date?->format('d/m/Y') ?? '-' }}</td></tr>
        <tr><td>Metode</td><td class="right">{{ strtoupper($sale->payment_method) }}</td></tr>
        <tr><td>Status</td><td class="right">LUNAS</td></tr>
    </table>
    <div class="rule"></div>
    <table>
        <thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Jumlah</th></tr></thead>
        <tbody>
        @foreach ($sale->details as $detail)
            <tr>
                <td>
                    {{ $detail->productVariant?->product?->name ?? 'Produk' }}
                    {{ $detail->productVariant?->name ? ' - ' . $detail->productVariant->name : '' }}
                    <div style="font-size: 9px; color: #555;">@ Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</div>
                </td>
                <td class="right">{{ (float) $detail->quantity }}</td>
                <td class="right">Rp {{ number_format($detail->quantity * $detail->unit_price, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="rule"></div>
    <table>
        <tr><td><strong>Total Bayar</strong></td><td class="right"><strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong></td></tr>
        @if ($sale->payment_method === 'cash')
            <tr><td>Tunai Diterima</td><td class="right">Rp {{ number_format($sale->cash_received ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Kembalian</td><td class="right">Rp {{ number_format($sale->change_amount ?? 0, 0, ',', '.') }}</td></tr>
        @endif
    </table>
    <div class="footer">Terima kasih telah berbelanja.</div>
</body>
</html>
