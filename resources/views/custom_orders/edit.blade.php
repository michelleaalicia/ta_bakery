@extends('layouts.admin')

@section('title', 'Edit Custom Order')
@section('page-title', 'Edit Custom Order')

@section('content')

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ $customOrder->order_number }}</h5>
        </div>

        <form action="{{ route('custom_orders.update', $customOrder) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="branch_id" class="form-label">Cabang</label>
                    <select name="branch_id" id="branch_id" class="form-select" required>
                        <option value="">Pilih Cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $customOrder->branch_id) == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="customer_name" class="form-control"
                            value="{{ old('customer_name', $customOrder->customer_name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nomor Telepon Pelanggan</label>
                        <input type="text" name="customer_contact" class="form-control"
                            value="{{ old('customer_contact', $customOrder->customer_contact) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Produk Custom</label>
                    <input type="text" name="product_name" class="form-control"
                        value="{{ old('product_name', $customOrder->product_name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Spesifikasi Pesanan</label>
                    <textarea name="specification" class="form-control" rows="3"
                        required>{{ old('specification', $customOrder->specification) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Pesanan</label>
                    <input type="number" name="quantity" class="form-control" min="1" step="1"
                        value="{{ old('quantity', $customOrder->quantity) }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pesanan</label>
                        <input type="date" name="order_date" id="order_date" class="form-control"
                            value="{{ old('order_date', $customOrder->order_date) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Dibutuhkan</label>
                        <input type="date" name="required_date" id="required_date" class="form-control"
                            value="{{ old('required_date', $customOrder->required_date) }}" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Rencana Bahan Baku</label>

                    <button type="button" id="addMaterial" class="btn btn-dark btn-sm">
                        <i class="bi bi-plus"></i> Tambah Bahan
                    </button>
                </div>

                <div id="materialContainer"></div>

                <div class="border rounded p-3 mt-3">
                    <div class="d-flex justify-content-between">
                        <span>Estimasi Biaya Bahan Baku</span>
                        <strong id="estimatedMaterialCost">Rp 0</strong>
                    </div>

                    <small class="text-muted">
                        Estimasi dihitung ulang menggunakan harga bahan di cabang pilihan.
                    </small>
                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <a href="{{ route('custom_orders.show', $customOrder) }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    @php
        $savedMaterials = old(
            'materials',
            $customOrder->materials->map(fn($material) => [
                'ingredient_master_id' => $material->ingredient_master_id,
                'quantity' => $material->quantity,
            ])->values()->all()
        );
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const branchSelect = document.getElementById('branch_id');
            const container = document.getElementById('materialContainer');
            const addButton = document.getElementById('addMaterial');
            const totalDisplay = document.getElementById('estimatedMaterialCost');
            const ingredients = @json($ingredients);
            const savedMaterials = @json($savedMaterials);

            let materialIndex = 0;

            function formatRupiah(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 2
                }).format(value);
            }

            function branchIngredients() {
                return ingredients.filter(item =>
                    String(item.branch_id) === branchSelect.value
                );
            }

            function updateTotal() {
                let total = 0;

                container.querySelectorAll('.material-row').forEach(row => {
                    const masterId = row.querySelector('.material-select').value;
                    const quantity = Number(row.querySelector('.material-quantity').value) || 0;

                    const ingredient = branchIngredients().find(item =>
                        String(item.master_id) === masterId
                    );

                    if (ingredient) {
                        total += quantity * Number(ingredient.unit_cost);
                    }
                });

                totalDisplay.textContent = formatRupiah(total);
            }

            function updateOptions() {
                const available = branchIngredients();

                container.querySelectorAll('.material-select').forEach(select => {
                    const selected = select.value;

                    select.innerHTML = '<option value="">Pilih Bahan Baku</option>';

                    available.forEach(item => {
                        const option = new Option(
                            item.name + ' | Stok: ' + item.stock + ' ' + item.unit
                            + ' | ' + formatRupiah(item.unit_cost) + '/' + item.unit,
                            item.master_id
                        );

                        select.add(option);
                    });

                    select.value = selected;

                    if (select.selectedIndex === -1) {
                        select.value = '';
                    }

                    select.disabled = !branchSelect.value;
                });

                updateTotal();
            }

            function addMaterial(masterId = '', quantity = '') {
                const index = materialIndex++;
                const row = document.createElement('div');

                row.className = 'material-row border rounded p-3 mb-2';

                row.innerHTML = `
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-7">
                                                    <label class="form-label">Bahan Baku</label>
                                                    <select name="materials[${index}][ingredient_master_id]"
                                                        class="form-select material-select" required></select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Jumlah Kebutuhan</label>
                                                    <input type="number" name="materials[${index}][quantity]"
                                                        class="form-control material-quantity"
                                                        min="0.001" step="0.001" required>
                                                </div>

                                                <div class="col-md-1">
                                                    <button type="button"
                                                        class="btn btn-outline-danger remove-material">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        `;

                container.appendChild(row);
                updateOptions();

                row.querySelector('.material-select').value = String(masterId);
                row.querySelector('.material-quantity').value = quantity;

                updateTotal();
            }

            branchSelect.addEventListener('change', function () {
                container.querySelectorAll('.material-select').forEach(select => {
                    select.value = '';
                });

                updateOptions();
            });

            addButton.addEventListener('click', function () {
                addMaterial();
            });

            container.addEventListener('change', updateTotal);
            container.addEventListener('input', updateTotal);

            container.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-material');

                if (!button) return;

                if (container.querySelectorAll('.material-row').length <= 1) {
                    alert('Minimal harus ada satu bahan baku.');
                    return;
                }

                button.closest('.material-row').remove();
                updateTotal();
            });

            const orderDate = document.getElementById('order_date');
            const requiredDate = document.getElementById('required_date');

            function updateRequiredDate() {
                requiredDate.min = orderDate.value;

                if (requiredDate.value && requiredDate.value < orderDate.value) {
                    requiredDate.value = '';
                }
            }

            orderDate.addEventListener('change', updateRequiredDate);
            updateRequiredDate();

            if (Object.keys(savedMaterials).length) {
                Object.values(savedMaterials).forEach(item => {
                    addMaterial(item.ingredient_master_id, item.quantity);
                });
            } else {
                addMaterial();
            }
        });
    </script>
@endpush