@extends('layouts.admin')

@section('title', 'Edit Produksi')
@section('page-title', 'Edit Produksi')

@section('content')

<div class="card">
    <div class="card-body">

        <form action="{{ route('production_orders.update', $productionOrder) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Produk --}}
            <div class="mb-3">
                <label class="form-label">Produk - Varian</label>

                <select name="product_variant_id" class="form-select" required>
                    <option value="">Pilih produk - varian</option>

                    @foreach ($variants as $variant)
                        <option value="{{ $variant->id }}"
                            {{ old('product_variant_id', $productionOrder->product_variant_id) == $variant->id ? 'selected' : '' }}>
                            {{ $variant->product->name }} - {{ $variant->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cabang --}}
            <div class="mb-3">
                <label class="form-label">Cabang</label>

                <select name="branch_id" class="form-select" required>
                    <option value="">Pilih cabang</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ old('branch_id', $productionOrder->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Jumlah --}}
            <div class="mb-3">
                <label class="form-label">Jumlah Produksi</label>

                <input
                    type="text"
                    name="quantity"
                    value="{{ old('quantity', rtrim(rtrim(number_format($productionOrder->quantity, 2, '.', ''), '0'), '.')) }}"
                    class="form-control"
                    placeholder="Contoh: 50"
                    required>
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
                                        class="form-select"
                                        required>

                                        <option value="">Pilih karyawan</option>

                                        @foreach ($users as $employee)
                                            <option value="{{ $employee->id }}"
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


    // Tambah karyawan
    let employeeIndex = {{ $productionOrder->users->count() }};

    const addEmployeeButton = document.getElementById('add-employee');
    const employeeContainer = document.getElementById('employee-container');

    addEmployeeButton.addEventListener('click', function () {

        const row = document.createElement('div');

        row.className = 'employee-row border rounded p-3 mb-2';

        row.innerHTML = `
            <div class="row">

                <div class="col-md-4">
                    <label class="form-label">Karyawan</label>

                    <select
                        name="employees[${employeeIndex}][user_id]"
                        class="form-select"
                        required>

                        <option value="">Pilih karyawan</option>

                        @foreach ($users as $employee)
                            <option value="{{ $employee->id }}">
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

</script>

@endsection