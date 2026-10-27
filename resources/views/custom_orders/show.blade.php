@extends('layouts.admin')



@section('title', 'Detail Custom Order')

@section('page-title', 'Detail Custom Order')



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

            <div class="d-flex align-items-center justify-content-between w-100">

                <h5 class="mb-0">{{ $customOrder->order_number }}</h5>



                <span class="badge bg-secondary">

                    {{ ucfirst($customOrder->status) }}

                </span>

            </div>

        </div>



        <div class="card-body">

            <h6 class="fw-bold mb-4">Informasi Pesanan</h6>



            <div class="row g-4">

                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Nama Pelanggan</div>

                    <div class="fw-semibold">{{ $customOrder->customer_name }}</div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Nomor Kontak</div>

                    <div class="fw-semibold">{{ $customOrder->customer_contact }}</div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Cabang</div>

                    <div class="fw-semibold">{{ $customOrder->branch?->name ?? '-' }}</div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Jumlah Pesanan</div>

                    <div class="fw-semibold">{{ $customOrder->quantity }}</div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Produk Custom</div>

                    <div class="fw-semibold">{{ $customOrder->product_name }}</div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Tanggal Pesanan</div>

                    <div class="fw-semibold">

                        {{ \Carbon\Carbon::parse($customOrder->order_date)->format('d/m/Y') }}

                    </div>

                </div>



                <div class="col-md-6 col-xl-3">

                    <div class="text-muted small mb-1">Tanggal Dibutuhkan</div>

                    <div class="fw-semibold">

                        {{ \Carbon\Carbon::parse($customOrder->required_date)->format('d/m/Y') }}

                    </div>

                </div>



                <div class="col-12">

                    <div class="text-muted small mb-2">Spesifikasi Pesanan</div>

                    <div class="border rounded p-3 bg-light" style="white-space: pre-line">{{ $customOrder->specification }}

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="card mb-3">

        <div class="card-header">

            <h5 class="mb-0">A. Estimasi Biaya Bahan Baku Langsung (BBB)</h5>

        </div>



        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Bahan Baku</th>

                            <th>Jumlah</th>

                            <th>Harga Satuan</th>

                            <th>Total</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($customOrder->materials as $material)

                            <tr>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $material->ingredientMaster?->name ?? '-' }}</td>

                                <td>

                                    {{ rtrim(rtrim(number_format($material->quantity, 3, ',', '.'), '0'), ',') }}

                                    {{ $material->unit }}

                                </td>

                                <td>Rp {{ number_format($material->estimated_unit_cost, 0, ',', '.') }}</td>

                                <td>Rp {{ number_format($material->estimated_total_cost, 0, ',', '.') }}</td>

                            </tr>

                        @endforeach

                    </tbody>

                    <tfoot>

                        <tr>

                            <th colspan="4">Total Estimasi BBB</th>

                            <th>Rp {{ number_format($materialCost, 0, ',', '.') }}</th>

                        </tr>

                    </tfoot>

                </table>

            </div>



            <small class="text-muted">

                Harga bahan merupakan estimasi saat pesanan dibuat.

                Biaya bahan aktual akan dicatat pada saat pelaksanaan produksi.

            </small>

        </div>

    </div>



    <div class="card mb-3">

        <div class="card-header">

            <h5 class="mb-0">B. Estimasi Tenaga Kerja dan Overhead</h5>

        </div>



        <div class="card-body">

            @if ($customOrder->status === 'draft' && auth()->user()->role?->name === 'Owner')

                <form method="POST" action="{{ route('custom_orders.estimation', $customOrder) }}">

                    @csrf

                    @method('PATCH')



                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h6 class="mb-0">Rencana Tenaga Kerja Langsung</h6>



                        <button type="button" id="addLabor" class="btn btn-dark btn-sm">

                            <i class="bi bi-plus"></i> Tambah Karyawan

                        </button>

                    </div>



                    <div id="laborContainer"></div>



                    <div class="border rounded p-3 mb-4 d-flex justify-content-between">

                        <strong>Total Estimasi BTKL</strong>

                        <strong id="laborPreview">Rp 0</strong>

                    </div>



                    <h6 class="mb-3">Estimasi Biaya Overhead Produksi</h6>



                    <div class="mb-3">

                        <label for="estimated_bop_cost" class="form-label">

                            Total BOP per Pesanan (Rp)

                        </label>



                        <div class="input-group">

                            <span class="input-group-text">Rp</span>



                            <input type="text" inputmode="numeric" pattern="[0-9]+" name="estimated_bop_cost"
                                id="estimated_bop_cost" class="form-control"
                                value="{{ old('estimated_bop_cost', $customOrder->estimated_bop_cost !== null ? (int) $customOrder->estimated_bop_cost : '') }}"
                                placeholder="Contoh: 50000" required>

                        </div>

                    </div>



                    <button type="submit" class="btn btn-dark">

                        Simpan Estimasi

                    </button>

                </form>

            @else

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead>

                            <tr>

                                <th>Karyawan</th>

                                <th>Pekerjaan</th>

                                <th>Jam Kerja</th>

                                <th>Tarif/Jam</th>

                                <th>Total Biaya</th>

                            </tr>

                        </thead>



                        <tbody>

                            @forelse ($customOrder->labors as $labor)

                                <tr>

                                    <td>{{ $labor->user?->name ?? '-' }}</td>

                                    <td>{{ preg_replace('/\s*\([0-9]+(?:\.[0-9]+)? jam\)/u', '', $labor->job_description) }}</td>

                                    <td>{{ rtrim(rtrim(number_format((float) $labor->estimated_hours, 2, ',', '.'), '0'), ',') }}
                                        jam</td>

                                    <td>Rp {{ number_format($labor->estimated_rate, 0, ',', '.') }}</td>

                                    <td>Rp {{ number_format($labor->estimated_cost, 0, ',', '.') }}</td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="text-center text-muted">

                                        Belum ada rencana tenaga kerja.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            @endif



            <div class="d-flex justify-content-between mb-2">

                <span>Estimasi BTKL Tersimpan</span>

                <strong>Rp {{ number_format($laborCost, 0, ',', '.') }}</strong>

            </div>



            <div class="d-flex justify-content-between">

                <span>Estimasi BOP Tersimpan</span>

                <strong>Rp {{ number_format($overheadCost, 0, ',', '.') }}</strong>

            </div>

        </div>

    </div>



    <div class="card mb-3">

        <div class="card-header">

            <h5 class="mb-0">C. Rekapitulasi Estimasi Biaya Pesanan</h5>

        </div>



        <div class="card-body">

            <table class="table table-bordered">

                <tbody>

                    <tr>

                        <td>Biaya Bahan Baku Langsung (BBB)</td>

                        <td class="text-end">Rp {{ number_format($materialCost, 0, ',', '.') }}</td>

                    </tr>

                    <tr>

                        <td>Biaya Tenaga Kerja Langsung (BTKL)</td>

                        <td class="text-end">Rp {{ number_format($laborCost, 0, ',', '.') }}</td>

                    </tr>

                    <tr>

                        <td>Biaya Overhead Pabrik (BOP)</td>

                        <td class="text-end">Rp {{ number_format($overheadCost, 0, ',', '.') }}</td>

                    </tr>

                    <tr class="table-light">

                        <th>Total Estimasi Biaya Pesanan</th>

                        <th class="text-end">Rp {{ number_format($totalCost, 0, ',', '.') }}</th>

                    </tr>

                    <tr>

                        <th>Estimasi HPP per Unit</th>

                        <th class="text-end">Rp {{ number_format($costPerUnit, 0, ',', '.') }}</th>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>



    <div class="card mb-3">

        <div class="card-header">

            <h5 class="mb-0">D. Penentuan Harga Jual</h5>

        </div>



        <div class="card-body">

            @if (

                    $customOrder->status === 'draft' &&

                    auth()->user()->role?->name === 'Owner' &&

                    $customOrder->labors->isNotEmpty() &&

                    $customOrder->estimated_bop_cost !== null

                )

                <div class="row g-3 mb-3">

                    <div class="col-md-4">

                        <label class="form-label">Markup atas Biaya (%)</label>

                        <input type="number" id="markup" class="form-control" min="0" step="0.01" value="30">

                    </div>



                    <div class="col-md-4">

                        <label class="form-label">Rekomendasi Harga Jual</label>

                        <div class="input-group">

                            <input type="text" id="recommendedPrice" class="form-control" readonly>

                            <button type="button" id="useRecommendedPrice" class="btn btn-outline-dark">Gunakan</button>

                        </div>

                    </div>

                </div>



                <form method="POST" action="{{ route('custom_orders.price', $customOrder) }}">

                    @csrf

                    @method('PATCH')



                    <div class="mb-3">

                        <label class="form-label">Harga Penawaran Total Pesanan (Rp)</label>

                        <input type="text" name="selling_price" id="sellingPrice" class="form-control" inputmode="numeric"
                            pattern="[0-9]+"
                            value="{{ old('selling_price', $customOrder->selling_price !== null ? (int) $customOrder->selling_price : '') }}"
                            required>

                    </div>



                    <button type="submit" class="btn btn-dark">

                        Simpan Harga Penawaran

                    </button>

                </form>

            @elseif ($customOrder->selling_price === null)

                <p class="text-muted mb-0">

                    @if (auth()->user()->role?->name === 'Owner')

                        Lengkapi estimasi biaya sebelum menetapkan harga penawaran.

                    @else

                        Harga penawaran belum ditetapkan oleh Owner.

                    @endif

                </p>

            @endif



            @if ($customOrder->selling_price !== null)

                <div class="d-flex justify-content-between mb-2">

                    <span>Harga Penawaran</span>

                    <strong>

                        Rp {{ number_format($customOrder->selling_price, 0, ',', '.') }}

                    </strong>

                </div>



                <div class="d-flex justify-content-between mb-2">

                    <span>Estimasi Laba Kotor</span>

                    <strong>

                        Rp {{ number_format($estimatedProfit, 0, ',', '.') }}

                    </strong>

                </div>



                <div class="d-flex justify-content-between">

                    <span>Estimasi Margin Laba Kotor</span>

                    <strong>{{ number_format($estimatedMargin, 2, ',', '.') }}%</strong>

                </div>

            @endif

        </div>

    </div>



    <div class="d-flex justify-content-between align-items-center mb-3">

        <a href="{{ route('custom_orders.index') }}" class="btn btn-secondary">

            Kembali

        </a>



        <div class="d-flex align-items-center gap-2">

            {{-- Konfirmasi Pesanan (draft) --}}

            @if (

                    $customOrder->status === 'draft' &&

                    auth()->user()->role?->name === 'Owner' &&

                    $customOrder->materials->isNotEmpty() &&

                    $customOrder->labors->isNotEmpty() &&

                    $customOrder->estimated_bop_cost !== null &&

                    $customOrder->selling_price !== null

                )

                <form action="{{ route('custom_orders.confirm', $customOrder) }}" method="POST"
                    onsubmit="return confirm('Pelanggan sudah menyetujui spesifikasi dan harga pesanan? Setelah dikonfirmasi, pesanan tidak dapat diedit.')">

                    @csrf

                    @method('PATCH')



                    <button type="submit" class="btn btn-success">

                        Konfirmasi Pesanan

                    </button>

                </form>

            @endif



            {{-- Buat Produksi (confirmed, sudah ada DP, belum ada produksi) --}}

            @if (

                    $customOrder->status === 'confirmed' &&

                    !$customOrder->production_order_id &&

                    $customOrder->payments->sum('amount') > 0

                )

                <form method="POST" action="{{ route('custom_orders.production.store', $customOrder) }}"
                    onsubmit="return confirm('Buat produksi untuk pesanan ini?')">

                    @csrf



                    <button type="submit" class="btn btn-primary">

                        Buat Produksi

                    </button>

                </form>

            @endif



            {{-- Lihat Produksi (sudah punya production order) --}}

            @if ($customOrder->production_order_id)

                <a href="{{ route('production_orders.show', $customOrder->production_order_id) }}" class="btn btn-success">

                    Lihat Produksi

                </a>

            @endif

        </div>

    </div>



    @include('custom_orders._delivery')



@endsection



@push('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const markupInput = document.getElementById('markup');

            const recommendedInput = document.getElementById('recommendedPrice');

            const sellingPriceInput = document.getElementById('sellingPrice');

            const useRecommendedPrice = document.getElementById('useRecommendedPrice');



            if (!markupInput) return;



            const totalCost = @json((float) $totalCost);



            function calculateRecommendedPrice() {

                const markup = Math.max(0, Number(markupInput.value) || 0);

                const price = Math.ceil(totalCost * (1 + markup / 100));



                recommendedInput.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);

            }



            markupInput.addEventListener('input', calculateRecommendedPrice);



            useRecommendedPrice.addEventListener('click', function () {

                const markup = Math.max(0, Number(markupInput.value) || 0);

                sellingPriceInput.value = Math.ceil(totalCost * (1 + markup / 100));

            });



            calculateRecommendedPrice();

        });

    </script>

@endpush



@php

    $laborEmployees = $employees->map(fn($user) => [

        'id' => $user->id,

        'name' => $user->name,

        'rate' => (float) $user->wage_rate_per_hour,

    ])->values()->all();



    $laborJobs = $jobDescriptions->pluck('name')->values()->all();



    // Data lama: satu pekerjaan/karyawan. Data baru: beberapa tugas

    // digabung per karyawan di database, kemudian diurai kembali untuk UI.

    $savedLabors = old('labors');



    if ($savedLabors === null) {

        $savedLabors = $customOrder->labors->flatMap(function ($labor) {

            $tasks = explode('; ', $labor->job_description);

            $rows = [];



            foreach ($tasks as $task) {

                if (preg_match('/^(.+) \(([0-9]+(?:\.[0-9]+)?) jam\)$/u', trim($task), $match)) {

                    $rows[] = [

                        'user_id' => $labor->user_id,

                        'job_description' => $match[1],

                        'estimated_hours' => $match[2],

                    ];

                } else {

                    // Kompatibel dengan estimasi sebelum revisi.

                    $rows[] = [

                        'user_id' => $labor->user_id,

                        'job_description' => $labor->job_description,

                        'estimated_hours' => $labor->estimated_hours,

                    ];

                    break;

                }

            }



            return $rows;

        })->values()->all();

    }

@endphp



@push('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const container = document.getElementById('laborContainer');

            const addButton = document.getElementById('addLabor');

            const preview = document.getElementById('laborPreview');



            if (!container || !addButton) return;



            const employees = @json($laborEmployees);

            const jobs = @json($laborJobs);

            const saved = @json($savedLabors);



            let index = 0;



            function rupiah(value) {

                return 'Rp ' + new Intl.NumberFormat('id-ID', {

                    minimumFractionDigits: 0,

                    maximumFractionDigits: 0

                }).format(value);

            }



            function updateCosts() {

                let total = 0;



                container.querySelectorAll('.labor-row').forEach(row => {

                    const userId = row.querySelector('.labor-user').value;

                    const hours = Number(row.querySelector('.labor-hours').value) || 0;

                    const employee = employees.find(item => String(item.id) === userId);



                    const rate = employee ? Number(employee.rate) : 0;

                    const cost = hours * rate;



                    row.querySelector('.labor-rate').textContent = rupiah(rate);

                    row.querySelector('.labor-cost').textContent = rupiah(cost);



                    total += cost;

                });



                preview.textContent = rupiah(total);

            }



            function addLabor(data = {}) {

                const rowIndex = index++;

                const row = document.createElement('div');



                row.className = 'labor-row border rounded p-3 mb-3';



                row.innerHTML = `

                                                                                    <div class="row g-3 align-items-end">

                                                                                        <div class="col-md-4">

                                                                                            <label class="form-label">Karyawan</label>

                                                                                            <select name="labors[${rowIndex}][user_id]"

                                                                                                class="form-select labor-user" required>

                                                                                                <option value="">Pilih karyawan</option>

                                                                                            </select>

                                                                                        </div>



                                                                                        <div class="col-md-4">

                                                                                            <label class="form-label">Pekerjaan</label>

                                                                                            <select name="labors[${rowIndex}][job_description]"

                                                                                                class="form-select labor-job" required>

                                                                                                <option value="">Pilih pekerjaan</option>

                                                                                            </select>

                                                                                        </div>



                                                                                        <div class="col-md-3">

                                                                                            <label class="form-label">Estimasi Jam Kerja</label>

                                                                                            <input type="text" inputmode="decimal"

                                                                                                name="labors[${rowIndex}][estimated_hours]"

                                                                                                class="form-control labor-hours"

                                                                                                placeholder="Contoh: 3" required>

                                                                                        </div>



                                                                                        <div class="col-md-1">

                                                                                            <button type="button"

                                                                                                class="btn btn-outline-danger remove-labor">

                                                                                                <i class="bi bi-trash"></i>

                                                                                            </button>

                                                                                        </div>

                                                                                    </div>



                                                                                    <div class="d-flex justify-content-between mt-3 small">

                                                                                        <span>Tarif/Jam: <strong class="labor-rate">Rp 0</strong></span>

                                                                                        <span>Estimasi Biaya: <strong class="labor-cost">Rp 0</strong></span>

                                                                                    </div>

                                                                                `;



                container.appendChild(row);



                const userSelect = row.querySelector('.labor-user');

                const jobSelect = row.querySelector('.labor-job');



                employees.forEach(employee => {

                    userSelect.add(new Option(employee.name, employee.id));

                });



                jobs.forEach(job => {

                    jobSelect.add(new Option(job, job));

                });



                userSelect.value = data.user_id ?? '';

                jobSelect.value = data.job_description ?? '';

                row.querySelector('.labor-hours').value =

                    data.estimated_hours !== undefined && data.estimated_hours !== ''

                        ? parseFloat(data.estimated_hours)

                        : '';



                updateCosts();

            }



            addButton.addEventListener('click', () => addLabor());



            container.addEventListener('input', updateCosts);

            container.addEventListener('change', updateCosts);



            container.addEventListener('click', function (event) {

                const button = event.target.closest('.remove-labor');



                if (!button) return;



                if (container.querySelectorAll('.labor-row').length <= 1) {

                    return;

                }



                button.closest('.labor-row').remove();

                updateCosts();

            });



            if (Object.keys(saved).length) {

                Object.values(saved).forEach(item => addLabor(item));

            } else {

                addLabor();

            }

        });

    </script>

@endpush