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
                            @if ($productionOrder->order_type === 'custom')
                                {{ $productionOrder->customOrder?->product_name ?? 'Pesanan Custom' }}
                            @else
                                {{ $productionOrder->productVariant?->product?->name ?? '-' }}
                                -
                                {{ $productionOrder->productVariant?->name ?? '-' }}
                            @endif
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


            {{-- Pisahkan bahan estimasi Custom Order dari bahan yang ditambahkan saat produksi. --}}
            @php
                $plannedMaterialMasterIds = $productionOrder->order_type === 'custom'
                    ? ($productionOrder->customOrder?->materials?->pluck('ingredient_master_id')->map(fn($id) => (string) $id)->all() ?? [])
                    : [];

                $mainProductionIngredients = $productionOrder->order_type === 'custom'
                    ? $productionOrder->ingredients->filter(fn($ingredient) => in_array((string) $ingredient->ingredient_master_id, $plannedMaterialMasterIds, true))->values()
                    : $productionOrder->ingredients;

                $extraProductionIngredients = $productionOrder->order_type === 'custom'
                    ? $productionOrder->ingredients->reject(fn($ingredient) => in_array((string) $ingredient->ingredient_master_id, $plannedMaterialMasterIds, true))->values()
                    : collect();
            @endphp

            @foreach ([
                    ['title' => 'Bahan Baku', 'items' => $mainProductionIngredients],
                    ['title' => 'Bahan Tambahan', 'items' => $extraProductionIngredients],
                ] as $section)
                @if ($section['title'] === 'Bahan Baku' || ($productionOrder->order_type === 'custom' && $section['items']->isNotEmpty()))
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">{{ $section['title'] }}</h6>
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
                                    @forelse ($section['items'] as $ingredient)
                                        @php
                                            $totalIngredientCost = $ingredient->pivot->quantity_used * $ingredient->pivot->unit_cost_used;
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $ingredient->name }}</td>
                                            <td>
                                                {{ rtrim(rtrim(number_format($ingredient->pivot->quantity_used, 2, ',', '.'), '0'), ',') }}
                                                {{ $ingredient->unit }}
                                            </td>
                                            <td>Rp {{ number_format($ingredient->pivot->unit_cost_used, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($totalIngredientCost, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Belum ada bahan baku.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach


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


            @if ($productionOrder->order_type === 'custom' && $productionOrder->status === 'in_progress')
                @php
                    $plannedMaterials = $productionOrder->customOrder?->materials?->keyBy('ingredient_master_id') ?? collect();
                    $plannedLabors = $productionOrder->customOrder?->labors?->keyBy('user_id') ?? collect();
                    $formatActual = fn($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                @endphp
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">Pencatatan Biaya Aktual</h6>
                        <p class="text-muted small mb-3">
                            Angka estimasi ditampilkan sebagai isian awal sebelum aktual pertama disimpan.
                            Sesuaikan dengan pemakaian dan jam kerja yang benar-benar terjadi.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <form action="{{ route('production_orders.custom-actuals.update', $productionOrder) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                @foreach ($mainProductionIngredients as $ingredient)
                                    @php
                                        $planned = $plannedMaterials->get($ingredient->ingredient_master_id);
                                        $initial = $customActualsSaved
                                            ? $ingredient->pivot->quantity_used
                                            : ($planned?->quantity ?? $ingredient->pivot->quantity_used);
                                    @endphp
                                    <div class="col-md-6">
                                        <label class="form-label" for="material-{{ $ingredient->id }}">
                                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                                        </label>
                                        <input type="number" id="material-{{ $ingredient->id }}"
                                            name="materials[{{ $ingredient->id }}]" class="form-control" min="0" step="any"
                                            value="{{ old('materials.' . $ingredient->id, $formatActual($initial)) }}" required>
                                        @if ($planned)
                                            <div class="form-text">Estimasi: {{ $formatActual($planned->quantity) }}
                                                {{ $planned->unit }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                <span class="fw-semibold">Bahan Tambahan</span>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="addCustomMaterial">+ Tambah
                                    Bahan</button>
                            </div>

                            {{-- Bahan tambahan yang SUDAH disimpan tetap tampil di sini dan tetap dikirim lewat materials[id].
                            --}}
                            @if ($extraProductionIngredients->isNotEmpty())
                                <div class="row g-3 mb-3">
                                    @foreach ($extraProductionIngredients as $ingredient)
                                        <div class="col-md-6">
                                            <label class="form-label" for="extra-saved-material-{{ $ingredient->id }}">
                                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                                            </label>
                                            <input type="number" id="extra-saved-material-{{ $ingredient->id }}"
                                                name="materials[{{ $ingredient->id }}]" class="form-control" min="0" step="any"
                                                value="{{ old('materials.' . $ingredient->id, $formatActual($ingredient->pivot->quantity_used)) }}"
                                                required>
                                            <div class="form-text">Bahan tambahan tersimpan</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            {{-- Baris yang ditambahkan saat ini, sebelum disimpan. --}}
                            <div id="customExtraMaterials"></div>

                            <h6 class="mt-4 mb-3">Jam Kerja Aktual</h6>
                            <div class="row g-3">
                                @foreach ($productionOrder->users as $user)
                                    @php
                                        $planned = $plannedLabors->get($user->id);
                                        $initial = $customActualsSaved
                                            ? $user->pivot->labor_hours
                                            : ($planned?->estimated_hours ?? $user->pivot->labor_hours);
                                    @endphp
                                    <div class="col-md-6">
                                        <label class="form-label" for="labor-{{ $user->id }}">
                                            {{ $user->name }} - {{ $user->pivot->job_description }}
                                        </label>
                                        <input type="number" id="labor-{{ $user->id }}" name="labors[{{ $user->id }}]"
                                            class="form-control" min="0" step="0.01"
                                            value="{{ $formatActual(old('labors.' . $user->id, $initial)) }}" required>
                                        @if ($planned)
                                            <div class="form-text">Estimasi: {{ $formatActual($planned->estimated_hours) }} jam</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                <span class="fw-semibold">Karyawan Tambahan</span>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="addCustomLabor">+ Tambah
                                    Karyawan</button>
                            </div>
                            <div id="customExtraLabors"></div>

                            <button type="submit" class="btn btn-primary mt-3">Simpan Biaya Aktual</button>
                        </form>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const ingredientList = @json($customMaterialOptions);
                        const employeeList = @json($customEmployeeOptions);
                        const jobList = @json($customJobOptions);
                        const materialContainer = document.getElementById('customExtraMaterials');
                        const laborContainer = document.getElementById('customExtraLabors');
                        let materialIndex = 0;
                        let laborIndex = 0;

                        function selectOptions(items, placeholder) {
                            return '<option value="">' + placeholder + '</option>' + items.map(item =>
                                '<option value="' + item.id + '">' + item.name + '</option>'
                            ).join('');
                        }
                        function jobOptions() {
                            const el = document.createElement('select');
                            el.className = 'form-select';
                            el.add(new Option('Pilih pekerjaan', ''));
                            jobList.forEach(name => el.add(new Option(name, name)));
                            return el;
                        }

                        document.getElementById('addCustomMaterial').addEventListener('click', function () {
                            const index = materialIndex++;
                            const row = document.createElement('div');
                            row.className = 'row g-2 align-items-end mb-2 custom-extra-row';
                            row.innerHTML = `<div class="col-md-6"><label class="form-label">Bahan</label><select class="form-select extra-material-select" name="extra_materials[${index}][ingredient_id]" required></select></div>
                                                <div class="col-md-4"><label class="form-label">Jumlah Aktual</label><input type="number" class="form-control" name="extra_materials[${index}][quantity]" min="0.01" step="0.01" required></div>
                                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-extra">Hapus</button></div>`;
                            materialContainer.appendChild(row);
                            const sel = row.querySelector('select');
                            sel.add(new Option('Pilih bahan', ''));
                            ingredientList.forEach(item => sel.add(new Option(item.name + ' (' + item.unit + ')', item.id)));
                        });

                        document.getElementById('addCustomLabor').addEventListener('click', function () {
                            const index = laborIndex++;
                            const row = document.createElement('div');
                            row.className = 'row g-2 align-items-end mb-2 custom-extra-row';
                            row.innerHTML = `<div class="col-md-4"><label class="form-label">Karyawan</label><select class="form-select extra-labor-select" name="extra_labors[${index}][user_id]" required></select></div>
                                                <div class="col-md-4"><label class="form-label">Pekerjaan</label><select class="form-select extra-job-select" name="extra_labors[${index}][job_description]" required></select></div>
                                                <div class="col-md-2"><label class="form-label">Jam Aktual</label><input type="number" class="form-control" name="extra_labors[${index}][labor_hours]" min="0.01" step="0.01" required></div>
                                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-extra">Hapus</button></div>`;
                            laborContainer.appendChild(row);
                            const sel = row.querySelector('.extra-labor-select');
                            sel.add(new Option('Pilih karyawan', ''));
                            employeeList.forEach(item => sel.add(new Option(item.name, item.id)));
                            const jobSelect = row.querySelector('.extra-job-select');
                            jobSelect.add(new Option('Pilih pekerjaan', ''));
                            jobList.forEach(name => jobSelect.add(new Option(name, name)));
                        });

                        [materialContainer, laborContainer].forEach(container => {
                            container.addEventListener('click', event => {
                                const button = event.target.closest('.remove-extra');
                                if (button) button.closest('.custom-extra-row').remove();
                            });
                        });
                    });
                </script>
            @endif

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
                            <div class="mt-3 text-muted small">HPP untuk pesanan produksi ini.</div>
                        </div>

                    </div>

                </div>

            </div>



            @if (
                    $productionOrder->status === 'completed' &&
                    $productionOrder->order_type === 'regular'
                )
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
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">HPP per unit</label>
                                <input type="text" class="form-control" readonly
                                    value="Rp {{ number_format((float) $hppPerUnit, 0, ',', '.') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga jual sekarang</label>
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
                        <p class="mt-3 mb-1" id="chosenProfitInfo"></p>
                        <p class="mb-1" id="priceDifference"></p>
                        <p class="text-danger small" id="priceWarning" role="alert"></p>
                        @if ($errors->has('final_price'))
                            <div class="alert alert-danger">{{ $errors->first('final_price') }}</div>
                        @endif
                        <div class="alert alert-warning small mb-0">
                            Harga jual berlaku untuk <strong>seluruh cabang</strong>.
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
                        const percentFormat = value => new Intl.NumberFormat('id-ID', {
                            maximumFractionDigits: 2
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
                                ? 'Keuntungan pada harga sekarang: ' + percentFormat((current - hpp) / hpp * 100) + '% dari HPP.'
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
                                'Keuntungan pilihan: ' + rupiah(difference) + ' per unit (' +
                                percentFormat(actualProfit) + '% dari HPP).';
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

                @if ($productionOrder->order_type === 'custom')
                    <a href="{{ route('production_orders.job-cost-sheet', $productionOrder) }}" class="btn btn-outline-dark">
                        <i class="bi bi-file-earmark-text"></i>
                        Job Cost Sheet
                    </a>
                @endif

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