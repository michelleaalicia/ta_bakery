@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <style>
        #productsTable th,
        #productsTable td {
            vertical-align: middle;
        }

        #productsTable th:first-child,
        #productsTable td:first-child {
            width: 45px !important;
            min-width: 45px;
            max-width: 45px;
            text-align: center !important;
        }

        #productsTable th:nth-child(4),
        #productsTable td:nth-child(4) {
            text-align: center !important;
        }

        #productsTable td:last-child {
            white-space: nowrap;
        }

        #productsTable thead th .dt-column-order {
            display: none !important;
        }

        #productsTable thead th.dt-orderable-asc,
        #productsTable thead th.dt-orderable-desc {
            position: relative;
            padding-right: 28px !important;
            cursor: pointer;
        }

        #productsTable thead th.dt-orderable-asc::after,
        #productsTable thead th.dt-orderable-desc::after {
            content: '↕';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #555 !important;
            font-size: 16px;
            font-weight: bold;
        }

        #productsTable thead th.dt-ordering-asc::after {
            content: '▲';
            color: #111 !important;
            font-size: 11px;
        }

        #productsTable thead th.dt-ordering-desc::after {
            content: '▼';
            color: #111 !important;
            font-size: 11px;
        }
    </style>

    <div class="card">

        <div class="card-header">
            <a href="{{ route('products.create') }}" class="btn btn-dark btn-sm">
                Tambah Produk
            </a>
            @if (auth()->user()->role?->name === 'Owner')
                <button type="button" class="btn btn-dark btn-sm btn-simulasi-harga" id="openProductPriceSimulator">
                    Simulasi Harga Jual
                </button>
            @endif
        </div>

        <div class="card-body">

            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-2">
                    <label for="filterCategory" class="form-label">
                        Kategori
                    </label>

                    <select id="filterCategory" class="form-select">
                        <option value="">Semua Kategori</option>

                        @foreach ($products->pluck('category.name')->filter()->unique()->sort() as $category)
                            <option value="{{ $category }}">
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">
                        Status
                    </label>

                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="pageLength" class="form-label">
                        Tampilkan Data
                    </label>

                    <select id="pageLength" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="-1">Semua</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="searchProduct" class="form-label">
                        Cari Produk
                    </label>

                    <input type="text" id="searchProduct" class="form-control" placeholder="Cari nama produk...">
                </div>

            </div>

            <div class="table-responsive">
                <table id="productsTable" class="table table-bordered table-hover w-100">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Jumlah Varian</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $product->name }}</td>

                                <td>{{ $product->category?->name ?? '-' }}</td>

                                <td>{{ $product->variants_count }}</td>

                                <td>{{ $product->description ?? '-' }}</td>

                                <td>
                                    @if ($product->status === 'active')
                                        <span class="badge text-bg-success">Aktif</span>
                                    @else
                                        <span class="badge text-bg-secondary">Nonaktif</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>

                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus produk ini?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    @if (auth()->user()->role?->name === 'Owner')
        <dialog id="productPriceDialog" aria-labelledby="productPriceTitle">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="productPriceTitle">Simulator Harga Jual</h5>
                <button type="button" class="btn-close" id="closeProductPriceSimulator" aria-label="Tutup"></button>
            </div>
            <div class="p-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="priceProduct">Pilih Produk</label>
                        <select id="priceProduct" class="form-select">
                            <option value="">Pilih produk</option>
                            @foreach ($priceProducts as $productOption)
                                <option value="{{ $productOption['id'] }}">{{ $productOption['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="priceVariant">Pilih Varian</label>
                        <select id="priceVariant" class="form-select" disabled>
                            <option value="">Pilih varian</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">HPP per unit</label>
                        <input type="text" class="form-control" id="productHpp" readonly value="-">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga jual sekarang</label>
                        <input type="text" class="form-control" id="productCurrentPrice" readonly value="-">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="productProfit">Target keuntungan (%)</label>
                        <input type="number" class="form-control" id="productProfit" min="0" max="1000" step="1" value="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rekomendasi sistem</label>
                        <input type="text" class="form-control fw-semibold" id="productRecommended" readonly>
                    </div>
                </div>
                <div id="productEligibility" class="small text-muted mb-2">Pilih produk dan varian terlebih dahulu.</div>
                <p id="productCurrentProfit" class="small mb-3"></p>
                <hr>
                <label class="form-label fw-semibold" for="productFinalPrice">Harga pilihan Owner (Rp)</label>
                <div class="row g-2">
                    <div class="col-sm-7">
                        <input type="text" data-rupiah inputmode="numeric" id="productFinalPrice" class="form-control"
                            placeholder="Contoh: 5000" disabled>
                    </div>
                    <div class="col-sm-5">
                        <button type="button" class="btn btn-outline-secondary w-100" id="productUseRecommended"
                            disabled>Gunakan Rekomendasi</button>
                    </div>
                </div>
                <p id="productChosenProfit" class="mt-3 mb-1"></p>
                <p id="productDifference" class="mb-1"></p>
                <p id="productWarning" class="text-danger small"></p>
                <div class="alert alert-warning small mb-0">Harga jual berlaku untuk <strong>seluruh cabang</strong>.</div>
            </div>
            <div class="p-3 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" id="cancelProductPriceSimulator">Tutup</button>
                <form action="{{ route('products.update-selling-price') }}" method="POST" id="productApplyPriceForm">
                    @csrf
                    <input type="hidden" name="product_id" id="submittedProductId">
                    <input type="hidden" name="product_variant_id" id="submittedVariantId">
                    <input type="hidden" name="final_price" id="submittedProductPrice">
                    <button class="btn btn-primary" id="productApplyPriceButton" type="submit" disabled>Terapkan Harga
                        Pilihan</button>
                </form>
            </div>
        </dialog>
        <style>
            #productPriceDialog {
                border: 0;
                border-radius: 8px;
                padding: 0;
                width: min(720px, 94vw);
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 12px 35px rgba(0, 0, 0, .22);
                background: #fff;
                color: #332820
            }

            #productPriceDialog::backdrop {
                background: rgba(0, 0, 0, .5)
            }

            #productPriceDialog .form-control[readonly] {
                background: #f8f9fa
            }
        </style>
    @endif

@endsection

@push('scripts')

    @if (auth()->user()->role?->name === 'Owner')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const products = @json($priceProducts);
                const dialog = document.getElementById('productPriceDialog');
                const productSelect = document.getElementById('priceProduct');
                const variantSelect = document.getElementById('priceVariant');
                const hppInput = document.getElementById('productHpp');
                const currentInput = document.getElementById('productCurrentPrice');
                const profitInput = document.getElementById('productProfit');
                const recommendedInput = document.getElementById('productRecommended');
                const finalInput = document.getElementById('productFinalPrice');
                const useButton = document.getElementById('productUseRecommended');
                const applyButton = document.getElementById('productApplyPriceButton');
                const eligibility = document.getElementById('productEligibility');
                const rp = value => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value);
                const pct = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(value);
                const raw = () => Number(finalInput.value.replace(/\D/g, ''));
                let selected = null;
                let recommended = 0;

                document.getElementById('openProductPriceSimulator').addEventListener('click', () => dialog.showModal());
                ['closeProductPriceSimulator', 'cancelProductPriceSimulator'].forEach(id => {
                    document.getElementById(id).addEventListener('click', () => dialog.close());
                });

                productSelect.addEventListener('change', function () {
                    const product = products.find(p => String(p.id) === productSelect.value);
                    variantSelect.innerHTML = '<option value="">Pilih varian</option>';
                    (product?.variants || []).forEach(variant => {
                        const option = document.createElement('option');
                        option.value = variant.id;
                        option.textContent = variant.name;
                        variantSelect.appendChild(option);
                    });
                    variantSelect.disabled = !product;
                    variantSelect.value = '';
                    selected = null;
                    finalInput.value = '';
                    update();
                });

                variantSelect.addEventListener('change', function () {
                    const product = products.find(p => String(p.id) === productSelect.value);
                    selected = product?.variants.find(v => String(v.id) === variantSelect.value) || null;
                    finalInput.value = selected && Number(selected.price) > 0 ? String(Math.round(selected.price)) : '';
                    finalInput.dispatchEvent(new Event('input', { bubbles: true }));
                    update();
                });

                function update() {
                    const hpp = Number(selected?.hpp || 0);
                    const current = Number(selected?.price || 0);
                    const eligible = !!selected && selected.has_completed && hpp > 0;
                    const profit = Number(profitInput.value);
                    const validProfit = profitInput.value !== '' && Number.isFinite(profit) && profit >= 0 && profit <= 1000;
                    recommended = eligible && validProfit ? Math.ceil(hpp * (1 + profit / 100)) : 0;
                    hppInput.value = eligible ? rp(hpp) : '-';
                    currentInput.value = selected ? (current > 0 ? rp(current) : 'Harga belum ditetapkan') : '-';
                    recommendedInput.value = eligible ? (validProfit ? rp(recommended) : 'Target tidak valid') : '-';
                    eligibility.textContent = !selected ? 'Pilih produk dan varian terlebih dahulu.'
                        : !selected.has_completed ? 'Varian belum pernah selesai diproduksi. Harga jual belum bisa ditentukan.'
                            : hpp <= 0 ? 'HPP varian belum tersedia. Harga jual belum bisa ditentukan.'
                                : 'HPP menggunakan produksi reguler selesai terakhir untuk varian ini.';
                    eligibility.className = eligible ? 'small text-muted mb-2' : 'small text-danger mb-2';
                    finalInput.disabled = !eligible;
                    useButton.disabled = !eligible || !validProfit;
                    document.getElementById('productCurrentProfit').textContent = eligible && current > 0
                        ? 'Keuntungan pada harga sekarang: ' + pct((current - hpp) / hpp * 100) + '% dari HPP.'
                        : eligible ? 'Harga jual belum ditetapkan, sehingga keuntungan pada harga sekarang belum dapat dihitung.' : '';
                    const chosen = raw();
                    const validPrice = finalInput.value !== '' && Number.isSafeInteger(chosen) && chosen >= 1 && chosen <= 9999999999999;
                    applyButton.disabled = !eligible || !validProfit || !validPrice;
                    document.getElementById('productChosenProfit').textContent = eligible && validPrice
                        ? 'Keuntungan pilihan: ' + rp(chosen - hpp) + ' per unit (' + pct((chosen - hpp) / hpp * 100) + '% dari HPP).' : '';
                    document.getElementById('productDifference').textContent = eligible && validPrice
                        ? (current > 0 ? 'Selisih dari harga sekarang: ' + rp(chosen - current) + '.' : 'Harga pertama untuk varian ini belum ditetapkan.') : '';
                    document.getElementById('productWarning').textContent = eligible && validPrice && chosen < hpp
                        ? 'Peringatan: harga pilihan di bawah HPP sehingga berpotensi rugi.' : '';
                }
                profitInput.addEventListener('input', update);
                finalInput.addEventListener('input', update);
                useButton.addEventListener('click', function () {
                    if (recommended > 0) {
                        finalInput.value = String(recommended);
                        finalInput.dispatchEvent(new Event('input', { bubbles: true }));
                        update();
                    }
                });
                document.getElementById('productApplyPriceForm').addEventListener('submit', function (event) {
                    update();
                    if (applyButton.disabled || !selected) { event.preventDefault(); return; }
                    const chosen = raw();
                    if (!confirm('Terapkan ' + rp(chosen) + ' di Produk dan POS untuk SEMUA cabang?' +
                        (chosen < selected.hpp ? '\nHarga ini di bawah HPP.' : ''))) {
                        event.preventDefault(); return;
                    }
                    document.getElementById('submittedProductId').value = productSelect.value;
                    document.getElementById('submittedVariantId').value = selected.id;
                    document.getElementById('submittedProductPrice').value = chosen;
                });
                update();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const table = new DataTable('#productsTable', {
                pageLength: 10,
                order: [[1, 'asc']],
                autoWidth: false,

                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },

                columnDefs: [
                    {
                        targets: [0, 6],
                        orderable: false
                    },
                    {
                        targets: 0,
                        width: '45px',
                        className: 'text-center'
                    },
                    {
                        targets: 3,
                        width: '120px',
                        className: 'text-center'
                    },
                    {
                        targets: 6,
                        width: '210px',
                        searchable: false
                    }
                ],

                language: {
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada produk',
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                },

                drawCallback: function () {
                    const api = this.api();
                    const start = api.page.info().start;

                    api.column(0, { page: 'current' }).nodes()
                        .each(function (cell, index) {
                            cell.textContent = start + index + 1;
                        });
                }
            });

            const searchInput = document.getElementById('searchProduct');
            const categoryInput = document.getElementById('filterCategory');
            const statusInput = document.getElementById('filterStatus');
            const pageLengthInput = document.getElementById('pageLength');

            function filterProducts() {
                const keyword = searchInput.value;
                const category = categoryInput.value;
                const status = statusInput.value;

                table.column(1).search(keyword);

                table.column(2).search(function (value) {
                    return category === '' || value.trim() === category;
                });

                table.column(5).search(function (value) {
                    return status === '' || value.trim() === status;
                });

                table.draw();
            }

            searchInput.addEventListener('input', filterProducts);
            categoryInput.addEventListener('change', filterProducts);
            statusInput.addEventListener('change', filterProducts);

            pageLengthInput.addEventListener('change', function () {
                table.page.len(Number(this.value)).draw();
            });

            filterProducts();

        });
    </script>
@endpush