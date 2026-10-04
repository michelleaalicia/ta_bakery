@extends('layouts.admin')

@section('title', 'Tambah Produksi')

@section('page-title', 'Tambah Produksi')

@section('content')

    <div class="card">
        <div class="card-body">

            <form action="{{ route('production_orders.store') }}" method="POST">
                @csrf

                {{-- Produk --}}
                <div class="mb-3">
                    <label class="form-label">Produk - Varian</label>

                    <select name="product_variant_id" class="form-select" required>
                        <option value="">Pilih produk - varian</option>

                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}" {{ old('product_variant_id') == $variant->id ? 'selected' : '' }}>
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
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Jumlah --}}
                <div class="mb-3">
                    <label class="form-label">Jumlah Produksi</label>

                    <input type="text" name="quantity" value="{{ old('quantity') }}" class="form-control"
                        placeholder="Contoh: 50" required>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Produksi</label>

                    <input type="date" name="production_date" value="{{ old('production_date', date('Y-m-d')) }}"
                        class="form-control" required>
                </div>

                {{-- BOP --}}
                <div class="mb-3">
                    <label class="form-label">Biaya Overhead Produksi</label>

                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="bop_cost" id="bop_cost" value="{{ old('bop_cost') }}" class="form-control"
                            placeholder="Contoh: 50000" required>
                    </div>

                    <div class="form-text">
                        Masukkan biaya overhead untuk produksi ini.
                    </div>
                </div>

                {{-- Profit --}}
                <div class="mb-3">
                    <label class="form-label">Profit yang Diinginkan</label>

                    <div class="input-group">
                        <input type="text" name="profit_percentage" id="profit_percentage"
                            value="{{ old('profit_percentage', 40) }}" class="form-control" placeholder="Contoh: 40"
                            required>

                        <span class="input-group-text">%</span>
                    </div>

                    <div class="form-text">
                        Masukkan persentase keuntungan yang diinginkan.
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

                                    <select name="employees[0][user_id]" class="form-select" required>

                                        <option value="">Pilih karyawan</option>

                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Pekerjaan</label>

                                    <input type="text" name="employees[0][job_description]" class="form-control"
                                        placeholder="Contoh: Membuat adonan" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Jam Kerja</label>

                                    <input type="number" name="employees[0][labor_hours]" class="form-control" min="0.01"
                                        step="0.01" placeholder="Contoh: 5" required>
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
                        <i class="bi bi-plus"></i> Tambah Karyawan
                    </button>
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label class="form-label">Catatan</label>

                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Masukkan catatan produksi">{{ old('notes') }}</textarea>
                </div>

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
        let employeeIndex = 1;

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

                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
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
                                    type="number"
                                    name="employees[${employeeIndex}][labor_hours]"
                                    class="form-control"
                                    min="0.01"
                                    step="0.01"
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
                button.closest('.employee-row').remove();
            }

        });
    </script>

@endsection