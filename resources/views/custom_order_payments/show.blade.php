@extends('layouts.admin')

@section('title', 'Detail Pembayaran')
@section('page-title', 'Detail Pembayaran Custom Order')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ $customOrder->order_number }}</h5>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Pelanggan</div>
                    <div class="fw-semibold">{{ $customOrder->customer_name }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small mb-1">Produk Custom</div>
                    <div class="fw-semibold">{{ $customOrder->product_name }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small mb-1">Cabang</div>
                    <div class="fw-semibold">{{ $customOrder->branch?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Total Tagihan</div>
                    <h4 class="mb-0">Rp {{ number_format($totalPrice, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Sudah Dibayar</div>
                    <h4 class="mb-0">Rp {{ number_format($paid, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-2">Sisa Tagihan</div>
                    <h4 class="mb-0">Rp {{ number_format($remaining, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    @if (
            $remaining > 0 &&
            $customOrder->status !== 'cancelled' &&
            in_array(auth()->user()->role?->name, ['Owner', 'Kasir'])
        )
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Catat Pembayaran</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('custom_order_payments.store', $customOrder) }}" method="POST">
                    @csrf

                    @php
                        $hasDp = $customOrder->payments->contains('payment_type', 'dp');
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Pembayaran</label>
                            <select name="payment_type" id="paymentType" class="form-select" required>
                                @if (!$hasDp)
                                    <option value="dp" @selected(old('payment_type') === 'dp')>
                                        DP
                                    </option>
                                @else
                                    <option value="pelunasan" selected>Pelunasan</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Pilih Metode</option>
                                @foreach (['Tunai', 'QRIS', 'Transfer'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                                        {{ $method }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nominal Pembayaran (Rp)</label>
                            <input type="text" name="amount" id="paymentAmount" inputmode="numeric" class="form-control"
                                value="{{ old('amount', $hasDp ? (int) $remaining : '') }}" placeholder="Masukkan nominal"
                                required @readonly($hasDp)>
                            @if ($hasDp)
                                <div class="form-text">Pelunasan harus sebesar sisa tagihan.</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-control"
                                value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                                required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-dark">
                            Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Riwayat Pembayaran</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Metode</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customOrder->payments as $payment)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                <td>{{ $payment->payment_type === 'dp' ? 'DP' : 'Pelunasan' }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Belum ada pembayaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ route('custom_order_payments.index') }}" class="btn btn-secondary">
        Kembali
    </a>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountInput = document.getElementById('paymentAmount');

            if (!amountInput || amountInput.readOnly) return;

            amountInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    </script>
@endpush