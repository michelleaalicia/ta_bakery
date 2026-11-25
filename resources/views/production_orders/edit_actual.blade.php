@extends('layouts.admin')
@section('title', 'Edit Biaya Aktual Produksi')
@section('page-title', 'Edit Biaya Aktual Produksi')

@section('content')
    <div class="card">
        <div class="card-body">
            <h6 class="fw-semibold">{{ $productionOrder->productVariant?->product?->name }} -
                {{ $productionOrder->productVariant?->name }}</h6>
            <p class="text-muted small">Produksi berlangsung. Ubah pemakaian bahan dan tenaga kerja aktual sebelum
                menyelesaikan produksi. Stok belum dikurangi pada tahap ini.</p>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('production_orders.update', $productionOrder) }}">
                @csrf
                @method('PUT')

                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <h6 class="mb-0">Bahan Baku</h6>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="add-material">+ Tambah Bahan</button>
                </div>
                <div id="material-container">
                    @foreach ($productionOrder->ingredients as $index => $ingredient)
                        <div class="material-row border rounded p-3 mb-2">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label">Bahan</label>
                                    <select class="form-select material-select" name="materials[{{ $index }}][ingredient_id]"
                                        required>
                                        <option value="">Pilih bahan</option>
                                        @foreach ($availableIngredients as $option)
                                            <option value="{{ $option->id }}" @selected($option->id === $ingredient->id)>
                                                {{ $option->master?->name }} ({{ $option->master?->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Digunakan</label>
                                    <input type="number" class="form-control" name="materials[{{ $index }}][quantity]"
                                        min="0.001" step="0.001" value="{{ (float) $ingredient->pivot->quantity_used }}"
                                        required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row">Hapus</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                    <h6 class="mb-0">Karyawan Produksi</h6>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="add-employee">+ Tambah Karyawan</button>
                </div>
                <div id="employee-container">
                    @foreach ($productionOrder->users as $index => $employee)
                        <div class="employee-row border rounded p-3 mb-2">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Karyawan</label>
                                    <select class="form-select employee-select" name="employees[{{ $index }}][user_id]"
                                        required>
                                        <option value="">Pilih karyawan</option>
                                        @foreach ($users as $option)
                                            @if ($productionOrder->branch_id == $option->branch_id)
                                                <option value="{{ $option->id }}" @selected($option->id === $employee->id)>
                                                    {{ $option->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pekerjaan</label>
                                    <select class="form-select" name="employees[{{ $index }}][job_description]" required>
                                        <option value="">Pilih pekerjaan</option>
                                        @foreach ($jobDescriptions as $job)
                                            <option value="{{ $job->name }}"
                                                @selected($employee->pivot->job_description === $job->name)>{{ $job->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Jam Kerja</label>
                                    <input type="number" class="form-control" name="employees[{{ $index }}][labor_hours]"
                                        min="0.01" step="0.01" value="{{ (float) $employee->pivot->labor_hours }}" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row">Hapus</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-bop-input :amount="$productionOrder->bop_cost" :details="$productionOrder->bopDetails" />

                <a href="{{ route('production_orders.show', $productionOrder) }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-dark">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const availableIngredients = @json($actualIngredientOptions);
            const availableEmployees = @json($actualEmployeeOptions);
            const availableJobs = @json($actualJobOptions);
            const materialContainer = document.getElementById('material-container');
            const employeeContainer = document.getElementById('employee-container');
            let materialIndex = {{ $productionOrder->ingredients->count() }};
            let employeeIndex = {{ $productionOrder->users->count() }};

            function addOptions(select, items, placeholder) {
                select.add(new Option(placeholder, ''));
                items.forEach(item => select.add(new Option(item.name, item.id)));
            }

            document.getElementById('add-material').addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'material-row border rounded p-3 mb-2';
                row.innerHTML = `<div class="row g-2 align-items-end">
                <div class="col-md-7"><label class="form-label">Bahan</label><select class="form-select material-select" name="materials[${materialIndex}][ingredient_id]" required></select></div>
                <div class="col-md-4"><label class="form-label">Jumlah Digunakan</label><input type="number" class="form-control" name="materials[${materialIndex}][quantity]" min="0.001" step="0.001" required></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm remove-row">Hapus</button></div></div>`;
                materialContainer.appendChild(row);
                addOptions(row.querySelector('select'), availableIngredients, 'Pilih bahan');
                materialIndex++;
            });

            document.getElementById('add-employee').addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'employee-row border rounded p-3 mb-2';
                row.innerHTML = `<div class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label">Karyawan</label><select class="form-select" name="employees[${employeeIndex}][user_id]" required></select></div>
                <div class="col-md-4"><label class="form-label">Pekerjaan</label><select class="form-select job-select" name="employees[${employeeIndex}][job_description]" required></select></div>
                <div class="col-md-3"><label class="form-label">Jam Kerja</label><input type="number" class="form-control" name="employees[${employeeIndex}][labor_hours]" min="0.01" step="0.01" required></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm remove-row">Hapus</button></div></div>`;
                employeeContainer.appendChild(row);
                addOptions(row.querySelector('select'), availableEmployees, 'Pilih karyawan');
                const job = row.querySelector('.job-select');
                job.add(new Option('Pilih pekerjaan', ''));
                availableJobs.forEach(name => job.add(new Option(name, name)));
                employeeIndex++;
            });

            [materialContainer, employeeContainer].forEach(container => {
                container.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-row');
                    if (button) button.closest('.border.rounded').remove();
                });
            });
        });
    </script>
@endsection