@extends('layouts.admin')

@section('title', 'Tambah Produksi')

@section('page-title', 'Tambah Produksi')

@section('content')

    <div class="card">
        <div class="card-body">

            <form action="{{ route('production_orders.store') }}" method="POST">
                @csrf

                {{-- Cabang --}}
                @if ($branches->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Cabang</label>

                        @if (auth()->user()->role?->name !== 'Owner')
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        @endif
                        <select name="branch_id" id="branch_id" class="form-select" required {{ auth()->user()->role?->name !== 'Owner' ? 'disabled' : '' }}>
                            <option value="">Pilih cabang</option>

                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (auth()->user()->role?->name !== 'Owner' ? auth()->user()->branch_id : old('branch_id')) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Produk --}}
                <div class="mb-3">
                    <label for="product_id" class="form-label">Produk</label>
                    <select id="product_id" class="form-select" required>
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Varian --}}
                <div class="mb-3">
                    <label for="product_variant_id" class="form-label">Varian Produk</label>
                    <select name="product_variant_id" id="product_variant_id" class="form-select" required disabled>
                        <option value="">Pilih produk terlebih dahulu</option>
                    </select>
                </div>


                {{-- Informasi Resep --}}
                <div id="recipe-info" class="border rounded p-3 mb-3 bg-light" style="display: none;">

                    <div class="fw-semibold mb-3">
                        <i class="bi bi-journal-text me-1"></i>
                        Informasi Resep
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                Hasil 1 Resep
                            </div>
                            <div class="fw-semibold" id="recipe-yield">
                                -
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                Estimasi Waktu 1 Resep
                            </div>
                            <div class="fw-semibold" id="recipe-time">
                                -
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                Target Produksi
                            </div>
                            <div class="fw-semibold" id="recipe-target">
                                -
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Batch Produksi --}}
                <div class="mb-3">
                    <label class="form-label">Batch Produksi</label>

                    <input type="text" name="batch_count" value="{{ old('batch_count', 1) }}" class="form-control"
                        placeholder="Contoh: 2" required>

                    <div class="form-text">
                        1 batch mengikuti jumlah hasil dari 1 resep. Contoh: 2 batch berarti 2 kali resep.
                    </div>
                </div>

                {{-- Tanggal Produksi --}}
                <div class="mb-3">
                    <label for="production_date" class="form-label">
                        Tanggal Produksi
                    </label>

                    <input type="date" id="production_date" name="production_date" class="form-control"
                        value="{{ old('production_date', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}"
                        required>
                </div>

                {{-- BOP --}}
                <div class="mb-3">
                    <label class="form-label">Biaya Overhead Produksi</label>

                    <div class="input-group">
                        <span class="input-group-text">Rp</span>

                        <input type="text" name="bop_cost" id="bop_cost" value="{{ old('bop_cost') }}" class="form-control"
                            placeholder="Contoh: 50000" required>
                    </div>
                </div>


                {{-- Karyawan --}}
                <div class="mb-3">
                    <label class="form-label">Karyawan Produksi</label>

                    <div id="employee-container">

                        <div class="employee-row border rounded p-3 mb-2">

                            <div class="row">

                                <div class="col-md-4">
                                    <label class="form-label">Karyawan</label>

                                    <select name="employees[0][user_id]" class="form-select employee-select" required>

                                        <option value="">Pilih karyawan</option>

                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" data-branch="{{ $user->branch_id ?? '' }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Pekerjaan</label>

                                    <select name="employees[0][job_description]" class="form-select" required>
                                        <option value="">Pilih pekerjaan</option>
                                        @foreach ($jobDescriptions as $jobDescription)
                                            <option value="{{ $jobDescription->name }}"
                                                @selected(old('employees.0.job_description') == $jobDescription->name)>
                                                {{ $jobDescription->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Jam Kerja</label>

                                    <input type="text" name="employees[0][labor_hours]" class="form-control"
                                        placeholder="Contoh: 5" required>
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-sm remove-employee" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                            </div>

                        </div>

                    </div>

                    <button type="button" id="add-employee" class="btn btn-secondary btn-sm">

                        <i class="bi bi-plus"></i>
                        Tambah Karyawan

                    </button>
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label class="form-label">Catatan</label>

                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Masukkan catatan produksi">{{ old('notes') }}</textarea>
                </div>

                {{-- Tombol --}}
                <div class="d-flex gap-2">

                    <a href="{{ route('production_orders.index') }}" class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit" class="btn btn-dark">
                        Simpan
                    </button>

                </div>

            </form>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            const variantSelect = document.getElementById('product_variant_id');

            const variants = @json($variantOptions);

            function updateVariants() {
                const productId = productSelect.value;

                variantSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = productId
                    ? 'Pilih varian produk'
                    : 'Pilih produk terlebih dahulu';

                variantSelect.appendChild(placeholder);
                variantSelect.disabled = !productId;

                variants
                    .filter(variant => String(variant.product_id) === productId)
                    .forEach(variant => {
                        const option = document.createElement('option');
                        option.value = variant.id;
                        option.textContent = variant.name;
                        variantSelect.appendChild(option);
                    });
            }


            const batchInput = document.querySelector('[name="batch_count"]');

            const recipeInfo = document.getElementById('recipe-info');
            const recipeYield = document.getElementById('recipe-yield');
            const recipeTime = document.getElementById('recipe-time');
            const recipeTarget = document.getElementById('recipe-target');

            const numberFormat = new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 2
            });

            function formatNumber(value) {
                return numberFormat.format(Number(value) || 0);
            }

            function formatDuration(minutes) {
                const totalMinutes = Number(minutes);

                if (!Number.isFinite(totalMinutes) || totalMinutes <= 0) {
                    return '-';
                }

                if (totalMinutes < 60) {
                    return formatNumber(totalMinutes) + ' menit';
                }

                const hours = Math.floor(totalMinutes / 60);
                const remainingMinutes = totalMinutes % 60;

                if (remainingMinutes === 0) {
                    return hours + ' jam';
                }

                return hours + ' jam ' +
                    formatNumber(remainingMinutes) + ' menit';
            }

            function updateRecipeInfo() {
                const selectedVariant = variants.find(
                    variant => String(variant.id) === variantSelect.value
                );

                if (!selectedVariant) {
                    recipeInfo.style.display = 'none';
                    recipeYield.textContent = '-';
                    recipeTime.textContent = '-';
                    recipeTarget.textContent = '-';
                    return;
                }

                const quantity = Number(selectedVariant.recipe_quantity);
                const unit = selectedVariant.recipe_unit || 'pcs';
                const batch = Number(batchInput.value);

                recipeInfo.style.display = 'block';

                recipeYield.textContent =
                    formatNumber(quantity) + ' ' + unit;

                recipeTime.textContent =
                    formatDuration(selectedVariant.production_time);

                recipeTarget.textContent =
                    Number.isInteger(batch) && batch > 0
                        ? formatNumber(quantity * batch) + ' ' + unit
                        : '-';
            }

            productSelect.addEventListener('change', function () {
                updateVariants();
                updateRecipeInfo();
            });

            variantSelect.addEventListener('change', updateRecipeInfo);

            batchInput.addEventListener('input', updateRecipeInfo);

            // Siapkan pilihan ketika halaman pertama dibuka.
            updateVariants();
            updateRecipeInfo();

            // Format BOP
            const bopInput = document.getElementById('bop_cost');

            if (bopInput) {
                bopInput.addEventListener('input', function () {

                    let value = this.value.replace(/\D/g, '');

                    if (value) {
                        this.value = new Intl.NumberFormat('id-ID').format(value);
                    }

                });
            }


            // Data karyawan
            const branchInput = document.getElementById('branch_id');

            let employeeIndex = 1;

            const addEmployeeButton = document.getElementById('add-employee');
            const employeeContainer = document.getElementById('employee-container');
            const jobDescriptions = @json($jobDescriptions->pluck('name')->values());

            function populateJobDescriptions(select) {
                jobDescriptions.forEach(name => {
                    select.add(new Option(name, name));
                });
            }

            function filterEmployees(select) {

                const selectedBranch = branchInput ? branchInput.value : '';

                Array.from(select.options).forEach(option => {

                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    const employeeBranch = option.dataset.branch || '';

                    if (branchInput) {
                        option.hidden = employeeBranch !== selectedBranch;
                    } else {
                        option.hidden = employeeBranch !== '';
                    }

                });

                if (
                    select.value &&
                    Array.from(select.options)
                        .find(option => option.value == select.value)?.hidden
                ) {
                    select.value = '';
                }
            }


            function filterAllEmployees() {

                document
                    .querySelectorAll('.employee-select')
                    .forEach(select => {
                        filterEmployees(select);
                    });

            }


            if (branchInput) {

                branchInput.addEventListener('change', function () {
                    filterAllEmployees();
                });

            }


            addEmployeeButton.addEventListener('click', function () {

                const row = document.createElement('div');

                row.className = 'employee-row border rounded p-3 mb-2';

                row.innerHTML = `
                                                                                                                    <div class="row">

                                                                                                                        <div class="col-md-4">

                                                                                                                            <label class="form-label">Karyawan</label>

                                                                                                                            <select
                                                                                                                                name="employees[${employeeIndex}][user_id]"
                                                                                                                                class="form-select employee-select"
                                                                                                                                required>

                                                                                                                                <option value="">Pilih karyawan</option>

                                                                                                                                @foreach ($users as $user)
                                                                                                                                    <option
                                                                                                                                        value="{{ $user->id }}"
                                                                                                                                        data-branch="{{ $user->branch_id ?? '' }}">
                                                                                                                                        {{ $user->name }}
                                                                                                                                    </option>
                                                                                                                                @endforeach

                                                                                                                            </select>

                                                                                                                        </div>


                                                                                                                        <div class="col-md-4">

                                                                                                                            <label class="form-label">Pekerjaan</label>

                                                                                                                            <select
                                                                                                                                name="employees[${employeeIndex}][job_description]"
                                                                                                                                class="form-select job-description-select"
                                                                                                                                required>
                                                                                                                                <option value="">Pilih pekerjaan</option>
                                                                                                                            </select>

                                                                                                                        </div>


                                                                                                                        <div class="col-md-3">

                                                                                                                            <label class="form-label">Jam Kerja</label>

                                                                                                                            <input
                                                                                                                                type="text"
                                                                                                                                name="employees[${employeeIndex}][labor_hours]"
                                                                                                                                class="form-control"
                                                                                                                                placeholder="Contoh: 5"
                                                                                                                                required>

                                                                                                                        </div>


                                                                                                                        <div class="col-md-1 d-flex align-items-end">

                                                                                                                            <button
                                                                                                                                type="button"
                                                                                                                                class="btn btn-danger btn-sm remove-employee">

                                                                                                                                <i class="bi bi-trash"></i>

                                                                                                                            </button>

                                                                                                                        </div>

                                                                                                                    </div>
                                                                                                                `;

                employeeContainer.appendChild(row);

                const newSelect = row.querySelector('.employee-select');
                const jobSelect = row.querySelector('.job-description-select');

                populateJobDescriptions(jobSelect);
                filterEmployees(newSelect);

                employeeIndex++;

            });

            // Hapus karyawan
            document.addEventListener('click', function (event) {

                const button = event.target.closest('.remove-employee');

                if (button) {

                    const rows = document.querySelectorAll('.employee-row');

                    if (rows.length > 1) {
                        button.closest('.employee-row').remove();
                    }

                }

            });

            // Filter saat halaman pertama kali dibuka
            filterAllEmployees();

        });
    </script>
@endsection