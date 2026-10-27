<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Pembayaran {{ $customOrder->order_number }}</title>
    <style>
        @page { margin: 13mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 10px; margin: 0; }
        .center { text-align: center; }
        .bakery { font-size: 17px; font-weight: bold; margin: 0 0 4px; }
        .branch { font-size: 10px; margin: 0 0 5px; }
        .address { color: #666; font-size: 9px; margin: 0 0 3px; }
        .line { border-top: 1px solid #333; margin: 12px 0; }
        .title { font-size: 13px; font-weight: bold; letter-spacing: 1px; margin-bottom: 5px; }
        .subtitle { color: #444; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 1px; vertical-align: top; }
        .label { width: 42%; color: #555; }
        .number { text-align: right; white-space: nowrap; }
        .summary td { padding: 7px 2px; border-bottom: 1px solid #e6e6e6; }
        .strong td { font-weight: bold; background: #f1f1f1; }
        .foot { margin-top: 24px; font-size: 9px; color: #555; line-height: 1.6; }
    </style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $jenis = $payment->payment_type === 'dp' ? 'DP / UANG MUKA' : 'PELUNASAN';
    $bakery = $customOrder->tenant?->name ?? 'Bakery';
    $branch = $customOrder->branch?->name;
    $alamat = $customOrder->branch?->address ?: $customOrder->tenant?->address;
    $telepon = $customOrder->branch?->phone_number ?: $customOrder->tenant?->phone_number;
@endphp
<div class="center">
    <div class="bakery">{{ $bakery }}</div>
    @if ($branch)
        <div class="branch">{{ $branch }}</div>
    @endif
    @if ($alamat)
        <div class="address">{{ $alamat }}</div>
    @endif
    @if ($telepon)
        <div class="address">Telp. {{ $telepon }}</div>
    @endif
</div>
<div class="line"></div>
<div class="center">
    <div class="title">NOTA PEMBAYARAN</div>
    <div class="subtitle">{{ $jenis }} | No. Nota: BYR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
</div>
<div class="line"></div>
<table>
    <tr><td class="label">Nomor Pesanan</td><td>{{ $customOrder->order_number }}</td></tr>
    <tr><td class="label">Pelanggan</td><td>{{ $customOrder->customer_name }}</td></tr>
    <tr><td class="label">Produk Custom</td><td>{{ $customOrder->product_name }}</td></tr>
    <tr><td class="label">Jumlah Pesanan</td><td>{{ rtrim(rtrim(number_format((float) $customOrder->quantity, 2, '.', ''), '0'), '.') }}</td></tr>
    <tr><td class="label">Tanggal Bayar</td><td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td></tr>
    <tr><td class="label">Metode</td><td>{{ $payment->payment_method }}</td></tr>
</table>
<div class="line"></div>
<table class="summary">
    <tr><td>Total Harga Pesanan</td><td class="number">{{ $rupiah($totalPrice) }}</td></tr>
    <tr><td>Pembayaran Sebelumnya</td><td class="number">{{ $rupiah($paidBefore) }}</td></tr>
    <tr class="strong"><td>Pembayaran {{ $payment->payment_type === 'dp' ? 'DP' : 'Pelunasan' }}</td><td class="number">{{ $rupiah($amount) }}</td></tr>
    <tr><td>Total Diterima s.d. Nota Ini</td><td class="number">{{ $rupiah($paidAfter) }}</td></tr>
    <tr class="strong"><td>Sisa Tagihan Setelah Pembayaran</td><td class="number">{{ $rupiah($remaining) }}</td></tr>
</table>
<div class="foot center">
    <strong>{{ $remaining <= 0 ? 'PEMBAYARAN LUNAS' : 'BUKTI PEMBAYARAN ' . ($payment->payment_type === 'dp' ? 'DP' : 'PELUNASAN') }}</strong><br>
    Nota diterbitkan berdasarkan transaksi pembayaran yang tercatat.<br>
    Terima kasih telah mempercayakan pesanan Anda kepada kami.
</div>
</body>
</html>
