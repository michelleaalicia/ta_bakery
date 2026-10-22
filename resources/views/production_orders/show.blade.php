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
                            {{ $productionOrder->branch?->name ?? 'Bakery utama' }}
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
                                        ($user->pivot->labor_rate_per_hour ?? $user->wage_rate_per_hour);
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
                                        Rp
                                        {{ number_format(($user->pivot->labor_rate_per_hour ?? $user->wage_rate_per_hour), 0, ',', '.') }}
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
                            <div class="mt-3 text-muted small">HPP historis untuk pesanan produksi ini.</div>
                        </div>

                    </div>

                </div>

            </div>



            @if ($productionOrder->status === 'completed')
                @php
                    $currentPrice = (float) $productionOrder->productVariant->price;
                    $hasPrice = $currentPrice > 0;
                @endphp

                <div class="border rounded p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="fw-semibold mb-1">Evaluasi Harga Jual</h6>
                        <div class="text-muted small">
                            Harga sekarang:
                            <strong>{{ $hasPrice ? 'Rp ' . number_format($currentPrice, 0, ',', '.') : 'Harga belum ditetapkan' }}</strong>.

                        </div>
                    </div>
                    <button type="button" id="openPriceSimulator" class="btn btn-outline-primary" @disabled($hppPerUnit <= 0)>
                        Simulasi Harga Jual
                    </button>
                </div>

                {{-- Dialog bawaan browser: tidak bergantung pada Bootstrap JavaScript. --}}
                <dialog id="priceSimulatorDialog" aria-labelledby="priceSimulatorTitle"
                    style="border: 0; border-radius: 8px; padding: 0; width: min(720px, 94vw); max-height: 90vh; overflow-y: auto; box-shadow: 0 12px 35px rgba(0,0,0,.22);">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="priceSimulatorTitle">Simulator Harga Jual</h5>
                        <button type="button" class="btn-close" id="closePriceSimulator" aria-label="Tutup"></button>
                    </div>
                    <div class="p-3">
                        <p class="text-muted small">Simulasi berdasarkan HPP produksi ini. Persentase keuntungan dihitung
                            sebagai markup dari HPP, bukan margin penjualan.</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">HPP per unit</label>
                                <input type="text" class="form-control" readonly
                                    value="Rp {{ number_format($hppPerUnit, 2, ',', '.') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga jual sekarang (semua cabang)</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $hasPrice ? 'Rp ' . number_format($currentPrice, 0, ',', '.') : 'Harga belum ditetapkan' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="simulatorProfit">Target keuntungan (%)</label>
                                <input type="number" class="form-control" id="simulatorProfit" min="0" max="1000" step="0.1"
                                    value="10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="simulatorPrice">Rekomendasi sistem</label>
                                <input type="text" class="form-control fw-semibold" id="simulatorPrice" readonly>
                            </div>
                        </div>
                        <p class="small mb-3" id="currentProfitInfo"></p>

                        <hr>
                        <label class="form-label fw-semibold" for="finalPrice">Harga pilihan Owner (Rp)</label>
                        <div class="row g-2">
                            <div class="col-sm-7">
                                <input type="number" min="1" max="9999999999999" step="1" id="finalPrice" class="form-control"
                                    placeholder="Contoh: 5000" value="{{ $hasPrice ? (int) $currentPrice : '' }}"
                                    @disabled(auth()->user()->role?->name !== 'Owner')>
                            </div>
                            <div class="col-sm-5">
                                <button type="button" class="btn btn-outline-secondary w-100" id="useRecommended"
                                    @disabled(auth()->user()->role?->name !== 'Owner')>Gunakan Rekomendasi</button>
                            </div>
                        </div>
                        <div class="form-text">Owner boleh menentukan harga bulat sendiri.</div>
                        <p class="mt-3 mb-1" id="chosenProfitInfo"></p>
                        <p class="mb-1" id="priceDifference"></p>
                        <p class="text-danger small" id="priceWarning" role="alert"></p>
                        @if ($errors->has('final_price'))
                            <div class="alert alert-danger">{{ $errors->first('final_price') }}</div>
                        @endif
                        <div class="alert alert-warning small mb-0">
                            Harga jual berlaku untuk <strong>seluruh cabang</strong>. Mengubah harga memengaruhi
                            POS berikutnya, bukan transaksi sebelumnya.
                        </div>
                    </div>
                    <div class="p-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="cancelPriceSimulator">Tutup</button>
                        @if (auth()->user()->role?->name === 'Owner')
                            <form action="{{ route('production_orders.apply-price', $productionOrder) }}" method="POST"
                                id="applyPriceForm">
                                @csrf
                                <input type="hidden" name="final_price" id="submittedFinalPrice">
                                <button type="submit" class="btn btn-primary" id="applyPriceButton">
                                    Terapkan Harga Pilihan
                                </button>
                            </form>
                        @endif
                    </div>
                </dialog>

                <style>
                    /* Background dialog simulator */
                    #priceSimulatorDialog {
                        background-color: #ffffff !important;
                        color: #212529 !important;
                        color-scheme: light;
                        border: none;
                        border-radius: 8px;
                    }

                    /* Semua teks di dialog */
                    #priceSimulatorDialog h5,
                    #priceSimulatorDialog p,
                    #priceSimulatorDialog label,
                    #priceSimulatorDialog .form-label,
                    #priceSimulatorDialog .fw-semibold {
                        color: #212529 !important;
                    }

                    /* Input simulator */
                    #priceSimulatorDialog .form-control {
                        background-color: #ffffff !important;
                        color: #212529 !important;
                        border: 1px solid #ced4da !important;
                    }

                    #priceSimulatorDialog .form-control[readonly] {
                        background-color: #f8f9fa !important;
                    }

                    /* Teks bantuan */
                    #priceSimulatorDialog .text-muted,
                    #priceSimulatorDialog .form-text {
                        color: #6c757d !important;
                    }

                    /* Peringatan tetap terbaca */
                    #priceSimulatorDialog .alert-warning {
                        background-color: #fff3cd !important;
                        color: #664d03 !important;
                    }

                    #priceSimulatorDialog .text-danger {
                        color: #dc3545 !important;
                    }

                    /* Tombol tutup */
                    #priceSimulatorDialog .btn-close {
                        filter: none !important;
                    }

                    /* Background belakang dialog */
                    #priceSimulatorDialog::backdrop {
                        background: rgba(0, 0, 0, 0.5);
                    }
                </style>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const hpp = @json((float) $hppPerUnit);
                        const current = @json($currentPrice);
                        const dialog = document.getElementById('priceSimulatorDialog');
                        const profitInput = document.getElementById('simulatorProfit');
                        const finalInput = document.getElementById('finalPrice');
                        const recommendedInput = document.getElementById('simulatorPrice');
                        const applyButton = document.getElementById('applyPriceButton');
                        const rupiah = value => 'Rp ' + new Intl.NumberFormat('id-ID', {
                            maximumFractionDigits: 0
                        }).format(value);
                        let recommended = 0;

                        document.getElementById('openPriceSimulator').addEventListener('click', function () {
                            dialog.showModal();
                        });
                        ['closePriceSimulator', 'cancelPriceSimulator'].forEach(function (id) {
                            document.getElementById(id).addEventListener('click', function () {
                                dialog.close();
                            });
                        });

                        function updateSimulator() {
                            const percent = Number(profitInput.value);
                            const validProfit = profitInput.value !== '' && Number.isFinite(percent) &&
                                percent >= 0 && percent <= 1000 && hpp > 0;
                            recommended = validProfit ? Math.ceil(hpp * (1 + percent / 100)) : 0;
                            recommendedInput.value = validProfit ? rupiah(recommended) : 'Target tidak valid';
                            const nowInfo = document.getElementById('currentProfitInfo');
                            nowInfo.textContent = current > 0
                                ? 'Keuntungan pada harga sekarang: ' + ((current - hpp) / hpp * 100).toFixed(2) + '% dari HPP.'
                                : 'Harga jual belum ditetapkan, sehingga keuntungan pada harga sekarang belum dapat dihitung.';

                            const chosen = Number(finalInput.value);
                            const validPrice = finalInput.value !== '' && Number.isSafeInteger(chosen) &&
                                chosen >= 1 && chosen <= 9999999999999;
                            if (applyButton) applyButton.disabled = !validProfit || !validPrice;

                            if (!validPrice) {
                                document.getElementById('chosenProfitInfo').textContent = '';
                                document.getElementById('priceDifference').textContent = '';
                                document.getElementById('priceWarning').textContent =
                                    finalInput.value === '' ? 'Masukkan harga pilihan Owner atau klik Gunakan Rekomendasi.' :
                                        'Harga pilihan harus berupa rupiah bulat lebih dari 0.';
                                return;
                            }

                            const difference = chosen - hpp;
                            const actualProfit = difference / hpp * 100;
                            document.getElementById('chosenProfitInfo').textContent =
                                'Keuntungan pilihan Owner: ' + rupiah(difference) + ' per unit (' +
                                actualProfit.toFixed(2) + '% dari HPP).';
                            document.getElementById('priceDifference').textContent = current > 0
                                ? 'Selisih dari harga sekarang: ' + rupiah(chosen - current) + '.'
                                : 'Harga pertama untuk varian ini belum ditetapkan.';
                            document.getElementById('priceWarning').textContent = chosen < hpp
                                ? 'Peringatan: harga pilihan di bawah HPP sehingga berpotensi rugi.' : '';
                        }

                        profitInput.addEventListener('input', updateSimulator);
                        finalInput.addEventListener('input', updateSimulator);
                        document.getElementById('useRecommended').addEventListener('click', function () {
                            if (recommended > 0) {
                                finalInput.value = recommended;
                                updateSimulator();
                            }
                        });
                        const form = document.getElementById('applyPriceForm');
                        if (form) form.addEventListener('submit', function (event) {
                            updateSimulator();
                            if (applyButton.disabled) {
                                event.preventDefault();
                                return;
                            }
                            const chosen = Number(finalInput.value);
                            if (!confirm('Terapkan ' + rupiah(chosen) + ' di Produk dan POS untuk SEMUA cabang?' +
                                (chosen < hpp ? '\nHarga ini di bawah HPP.' : ''))) {
                                event.preventDefault();
                                return;
                            }
                            document.getElementById('submittedFinalPrice').value = chosen;
                        });
                        updateSimulator();
                    });
                </script>
            @endif

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