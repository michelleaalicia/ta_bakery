@extends('layouts.admin')

@section('title', 'Tambah Custom Order')
@section('page-title', 'Tambah Custom Order')

@section('content')

    <div class="card">
        <form method="POST" action="{{ route('custom_orders.store') }}">
            @csrf

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
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nomor Telepon Pelanggan</label>
                        <input type="text" name="customer_contact" class="form-control"
                            value="{{ old('customer_contact') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Produk Custom</label>
                    <input type="text" name="product_name" class="form-control" value="{{ old('product_name') }}"
                        placeholder="Contoh: Birthday Cake Coklat" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Spesifikasi Pesanan</label>
                    <textarea name="specification" class="form-control" rows="3"
                        placeholder="Ukuran, rasa, bentuk, dekorasi, tulisan, dan permintaan khusus"
                        required>{{ old('specification') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Pesanan</label>
                    <input type="number" name="quantity" class="form-control" min="1" step="1"
                        value="{{ old('quantity', 1) }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pesanan</label>
                        <input type="date" name="order_date" id="order_date" class="form-control"
                            value="{{ old('order_date', now()->toDateString()) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Dibutuhkan</label>
                        <input type="date" name="required_date" id="required_date" class="form-control"
                            value="{{ old('required_date') }}" required>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Estimasi Biaya Bahan Baku</span>
                        <strong id="estimatedMaterialCost">Rp 0</strong>
                    </div>
                    <small class="text-muted">
                        Estimasi menggunakan harga rata-rata bahan di cabang pilihan.
                        Belum termasuk tenaga kerja dan overhead.
                    </small>
                </div>

            </div>

            <div class="card-footer">
                <a href="{{ route('custom_orders.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-dark">
                    Simpan Draft
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const branchSelect = document.getElementById('branch_id');
            const container = document.getElementById('materialContainer');
            const addButton = document.getElementById('addMaterial');
            const totalDisplay = document.getElementById('estimatedMaterialCost');
            const ingredients = @json($ingredients);
            const oldMaterials = @json(old('materials', []));
            let materialIndex = 0;

            function formatRupiah(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 2
                }).format(value);
            }

            function getBranchIngredients() {
                return ingredients.filter(ingredient =>
                    String(ingredient.branch_id) === branchSelect.value
                );
            }

            function updateTotal() {
                let total = 0;

                container.querySelectorAll('.material-row').forEach(row => {
                    const masterId = row.querySelector('.material-select').value;
                    const quantity = Number(row.querySelector('.material-quantity').value) || 0;

                    const ingredient = getBranchIngredients().find(item =>
                        String(item.master_id) === masterId
                    );

                    total += quantity * (ingredient ? Number(ingredient.unit_cost) : 0);
                });

                totalDisplay.textContent = formatRupiah(total);
            }

            function updateMaterialOptions() {
                const available = getBranchIngredients();

                container.querySelectorAll('.material-select').forEach(select => {
                    const current = select.value;

                    select.innerHTML = '<option value="">Pilih Bahan Baku</option>';

                    available.forEach(ingredient => {
                        const option = document.createElement('option');
                        option.value = ingredient.master_id;
                        option.textContent = ingredient.name
                            + ' | Stok: ' + ingredient.stock
                            + ' ' + ingredient.unit
                            + ' | ' + formatRupiah(ingredient.unit_cost) + '/' + ingredient.unit;
                        select.appendChild(option);
                    });

                    select.value = current;

                    if (select.selectedIndex === -1) {
                        select.value = '';
                    }

                    select.disabled = !branchSelect.value;
                });

                updateTotal();
            }

            function addMaterial(selectedId = '', quantity = '') {
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
                                        <input type="number"
                                            name="materials[${index}][quantity]"
                                            class="form-control material-quantity"
                                            min="0.001" step="0.001"
                                            placeholder="Jumlah bahan" required>
                                    </div>

                                    <div class="col-md-1">
                                        <button type="button"
                                            class="btn btn-outline-danger remove-material"
                                            style="height:38px;width:42px">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;

                container.appendChild(row);

                updateMaterialOptions();

                row.querySelector('.material-select').value = String(selectedId);
                row.querySelector('.material-quantity').value = quantity;

                updateTotal();
            }

            branchSelect.addEventListener('change', function () {
                container.querySelectorAll('.material-select').forEach(select => {
                    select.value = '';
                });

                updateMaterialOptions();
            });

            addButton.addEventListener('click', function () {
                addMaterial();
            });

            container.addEventListener('input', updateTotal);
            container.addEventListener('change', updateTotal);

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

            if (oldMaterials.length) {
                oldMaterials.forEach(item => {
                    addMaterial(item.ingredient_master_id, item.quantity);
                });
            } else {
                addMaterial();
            }
        });
    </script>
@endpush