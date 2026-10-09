@extends('layouts.admin')

@section('title', 'Edit Produksi')

@section('page-title', 'Edit Produksi')

@section('content')

<div class="card">
    <div class="card-body">

        <form action="{{ route('production_orders.update', $productionOrder) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Cabang --}}
            @if ($branches->count() > 0)

                <div class="mb-3">
                    <label class="form-label">Cabang</label>

                    <select name="branch_id" id="branch_id" class="form-select" required>

                        <option value="">Pilih cabang</option>

                        @foreach ($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                {{ old('branch_id', $productionOrder->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

            @endif


            {{-- Produk --}}
            <div class="mb-3">
                <label class="form-label">Produk - Varian</label>

                <select name="product_variant_id" class="form-select" required>

                    <option value="">Pilih produk - varian</option>

                    @foreach ($variants as $variant)

                        <option
                            value="{{ $variant->id }}"
                            {{ old('product_variant_id', $productionOrder->product_variant_id) == $variant->id ? 'selected' : '' }}>

                            {{ $variant->product->name }} - {{ $variant->name }}

                        </option>

                    @endforeach

                </select>
            </div>


            {{-- Batch Produksi --}}
            <div class="mb-3">

                <label class="form-label">Batch Produksi</label>

                <input
                    type="text"
                    name="batch_count"
                    value="{{ old('batch_count', rtrim(rtrim(number_format($productionOrder->batch_count, 2, '.', ''), '0'), '.')) }}"
                    class="form-control"
                    placeholder="Contoh: 2"
                    required>

                <div class="form-text">
                    1 batch mengikuti jumlah hasil dari 1 resep. Contoh: 2 batch berarti 2 kali resep.
                </div>

            </div>


            {{-- Tanggal --}}
            <div class="mb-3">

                <label class="form-label">Tanggal Produksi</label>

                <input
                    type="date"
                    name="production_date"
                    value="{{ old('production_date', $productionOrder->production_date) }}"
                    class="form-control"
                    required>

            </div>


            {{-- BOP --}}
            <div class="mb-3">

                <label class="form-label">Biaya Overhead Produksi</label>

                <div class="input-group">

                    <span class="input-group-text">Rp</span>

                    <input
                        type="text"
                        name="bop_cost"
                        id="bop_cost"
                        value="{{ old('bop_cost', number_format($productionOrder->bop_cost, 0, ',', '.')) }}"
                        class="form-control"
                        placeholder="Contoh: 50000"
                        required>

                </div>

                <div class="form-text">
                    Masukkan biaya overhead untuk produksi ini.
                </div>

            </div>


            {{-- Karyawan --}}
            <div class="mb-3">

                <label class="form-label">Karyawan Produksi</label>

                <div id="employee-container">

                    @foreach ($productionOrder->users as $index => $user)

                        <div class="employee-row border rounded p-3 mb-2">

                            <div class="row">

                                <div class="col-md-4">

                                    <label class="form-label">Karyawan</label>

                                    <select
                                        name="employees[{{ $index }}][user_id]"
                                        class="form-select employee-select"
                                        required>

                                        <option value="">Pilih karyawan</option>

                                        @foreach ($users as $employee)

                                            <option
                                                value="{{ $employee->id }}"
                                                data-branch="{{ $employee->branch_id ?? '' }}"
                                                {{ $user->id == $employee->id ? 'selected' : '' }}>

                                                {{ $employee->name }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">Pekerjaan</label>

                                    <input
                                        type="text"
                                        name="employees[{{ $index }}][job_description]"
                                        value="{{ $user->pivot->job_description }}"
                                        class="form-control"
                                        placeholder="Contoh: Membuat adonan"
                                        required>

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label">Jam Kerja</label>

                                    <input
                                        type="text"
                                        name="employees[{{ $index }}][labor_hours]"
                                        value="{{ rtrim(rtrim(number_format($user->pivot->labor_hours, 2, '.', ''), '0'), '.') }}"
                                        class="form-control"
                                        placeholder="Contoh: 5"
                                        required>

                                </div>


                                <div class="col-md-1 d-flex align-items-end">

                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm remove-employee"
                                        {{ $productionOrder->users->count() <= 1 ? 'disabled' : '' }}>

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>


                <button
                    type="button"
                    id="add-employee"
                    class="btn btn-secondary btn-sm">

                    <i class="bi bi-plus"></i>
                    Tambah Karyawan

                </button>

            </div>


            {{-- Catatan --}}
            <div class="mb-3">

                <label class="form-label">Catatan</label>

                <textarea
                    name="notes"
                    class="form-control"
                    rows="3"
                    placeholder="Masukkan catatan produksi">{{ old('notes', $productionOrder->notes) }}</textarea>

            </div>


            {{-- Tombol --}}
            <div class="d-flex gap-2">

                <a
                    href="{{ route('production_orders.show', $productionOrder) }}"
                    class="btn btn-secondary">

                    Kembali

                </a>

                <button
                    type="submit"
                    class="btn btn-dark">

                    Simpan Perubahan

                </button>

            </div>

        </form>

    </div>
</div>


<script>

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


    // Filter karyawan berdasarkan cabang
    const branchInput = document.getElementById('branch_id');

    let employeeIndex = {{ $productionOrder->users->count() }};

    const addEmployeeButton = document.getElementById('add-employee');

    const employeeContainer = document.getElementById('employee-container');


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

        const selectedOption = Array.from(select.options)
            .find(option => option.value == select.value);

        if (selectedOption && selectedOption.hidden) {
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


    // Tambah karyawan
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

                        @foreach ($users as $employee)

                            <option
                                value="{{ $employee->id }}"
                                data-branch="{{ $employee->branch_id ?? '' }}">

                                {{ $employee->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">Pekerjaan</label>

                    <input
                        type="text"
                        name="employees[${employeeIndex}][job_description]"
                        class="form-control"
                        placeholder="Contoh: Membuat adonan"
                        required>

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


    // Jalankan saat halaman dibuka
    filterAllEmployees();

</script>

@endsection