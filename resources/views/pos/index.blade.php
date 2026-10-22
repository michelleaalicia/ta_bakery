@extends('layouts.admin')

@section('title', 'Point of Sale')
@section('page-title', 'Point of Sale')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="pos-page">
        <div class="pos-toolbar">
            @if ($isOwner && $branches->isNotEmpty())
                <div class="pos-branch">
                    <label for="branchSelect">Cabang</label>

                    <select id="branchSelect" class="form-select">
                        @forelse ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @empty
                            <option value="">Belum ada cabang</option>
                        @endforelse
                    </select>

                </div>
            @elseif (!$isOwner && $branches->isNotEmpty())
                <div class="pos-branch">
                    <label>Cabang aktif</label>
                    <div class="branch-label">
                        {{ $branches->firstWhere('id', $selectedBranchId)?->name ?? 'Cabang belum ditentukan' }}
                    </div>
                </div>
            @endif
        </div>

        <div class="pos-layout">
            <section class="pos-catalog">
                <div class="pos-panel">
                    <div class="catalog-toolbar">
                        <div class="catalog-search">
                            <i class="bi bi-search"></i>
                            <input type="search" id="searchInput" class="form-control"
                                placeholder="Cari produk atau varian...">
                        </div>

                        <select id="categoryFilter" class="form-select">
                            <option value="">Semua kategori</option>
                        </select>
                    </div>

                    <div class="catalog-summary">
                        <span id="productCount"></span>
                    </div>

                    <div id="productGrid" class="product-grid">
                        <div class="catalog-message">
                            <span class="spinner-border spinner-border-sm"></span>
                            Memuat produk...
                        </div>
                    </div>
                </div>
            </section>

            <aside class="pos-cart-column">
                <div class="pos-panel cart-panel">
                    <div class="cart-heading">
                        <div>
                            <h5 class="mb-1">Keranjang</h5>
                            <p class="text-muted mb-0" id="cartCount">0 item</p>
                        </div>
                        <button type="button" id="clearCart" class="btn btn-sm btn-outline-danger">
                            Kosongkan
                        </button>
                    </div>

                    <div id="cartItems" class="cart-items">
                        <div class="empty-cart">
                            <i class="bi bi-cart3"></i>
                            <p>Keranjang masih kosong</p>
                            <span>Pilih produk untuk memulai transaksi.</span>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Jumlah item</span>
                            <strong id="summaryItems">0</strong>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total pembayaran</span>
                            <strong id="grandTotal">Rp0</strong>
                        </div>
                    </div>

                    <div class="checkout-form">
                        <label for="paymentMethod" class="form-label">
                            Metode pembayaran
                        </label>
                        <select id="paymentMethod" class="form-select">
                            <option value="cash">Tunai</option>
                            <option value="qris">QRIS</option>
                        </select>

                        <div id="cashSection" class="mt-3">
                            <label for="cashReceived" class="form-label">
                                Uang diterima
                            </label>
                            <input type="text" id="cashReceived" class="form-control" inputmode="numeric" pattern="[0-9]*"
                                placeholder="Masukkan jumlah uang">

                            <div class="change-box">
                                <span>Kembalian</span>
                                <strong id="changeAmount">Rp0</strong>
                            </div>
                        </div>

                        <button type="button" id="checkoutButton" class="btn btn-dark w-100 checkout-button">
                            Proses pembayaran
                        </button>

                        <div id="checkoutMessage" class="mt-3" aria-live="polite"></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <style>
        .pos-page {
            --pos-border: #e5e7eb;
            --pos-muted: #6b7280;
            color: #212529;
        }

        .pos-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .pos-toolbar h4 {
            font-size: 22px;
            font-weight: 700;
        }

        .pos-branch {
            width: 230px;
            max-width: 100%;
        }

        .pos-branch label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 600;
            color: var(--pos-muted);
        }

        .branch-label {
            padding: 10px 12px;
            border: 1px solid var(--pos-border);
            background: white;
            border-radius: 7px;
        }

        .pos-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 370px;
            align-items: start;
            gap: 20px;
        }

        .pos-panel {
            background: #fff;
            border: 1px solid var(--pos-border);
            border-radius: 10px;
            overflow: hidden;
        }

        .catalog-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            gap: 12px;
            padding: 18px;
            border-bottom: 1px solid var(--pos-border);
        }

        .catalog-search {
            position: relative;
        }

        .catalog-search>i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #858b94;
        }

        .catalog-search input {
            padding-left: 36px;
        }

        .catalog-toolbar .form-control,
        .catalog-toolbar .form-select,
        .checkout-form .form-control,
        .checkout-form .form-select {
            min-height: 42px;
        }

        .catalog-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            font-size: 13px;
            color: var(--pos-muted);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 0 18px 18px;
        }

        .product-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            border: 1px solid var(--pos-border);
            border-radius: 9px;
            overflow: hidden;
            transition: border-color .15s, box-shadow .15s;
        }

        .product-card:hover {
            border-color: #adb5bd;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #f8f9fa;
            overflow: hidden;
            padding: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .product-image .image-placeholder {
            font-size: 32px;
            color: #adb5bd;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 12px;
        }

        .product-category {
            font-size: 11px;
            color: var(--pos-muted);
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .product-variant {
            font-size: 12px;
            color: var(--pos-muted);
            margin-top: 3px;
        }

        .product-price {
            font-weight: 700;
            font-size: 14px;
            margin-top: 12px;
        }

        .product-stock {
            font-size: 12px;
            color: #198754;
            margin-top: 4px;
        }

        .product-stock.out {
            color: #dc3545;
        }

        .add-product {
            margin-top: 12px;
            width: 100%;
            font-size: 13px;
        }

        .add-product:disabled {
            cursor: not-allowed;
            opacity: .6;
        }

        .catalog-message {
            grid-column: 1 / -1;
            padding: 45px 12px;
            text-align: center;
            color: var(--pos-muted);
        }

        .cart-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px;
            border-bottom: 1px solid var(--pos-border);
        }

        .cart-heading h5 {
            font-weight: 700;
        }

        .cart-heading p {
            font-size: 12px;
        }

        .cart-items {
            padding: 4px 18px;
            max-height: 350px;
            overflow-y: auto;
        }

        .empty-cart {
            padding: 35px 8px;
            text-align: center;
            color: var(--pos-muted);
        }

        .empty-cart i {
            display: block;
            font-size: 32px;
            margin-bottom: 12px;
        }

        .empty-cart p {
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        .empty-cart span {
            font-size: 12px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            padding: 14px 0;
            border-bottom: 1px solid #f0f1f3;
        }

        .cart-item:last-child {
            border-bottom: 0;
        }

        .cart-item-name {
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .cart-item-price {
            font-size: 12px;
            color: var(--pos-muted);
            margin-top: 4px;
        }

        .cart-item-subtotal {
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }

        .quantity-control button {
            width: 27px;
            height: 27px;
            padding: 0;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .quantity-value {
            min-width: 20px;
            text-align: center;
            font-size: 13px;
        }

        .remove-item {
            border: 0;
            background: transparent;
            color: #dc3545;
            margin-left: 4px;
            padding: 2px;
        }

        .cart-summary {
            padding: 16px 18px;
            background: #f8f9fa;
            border-top: 1px solid var(--pos-border);
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .total-row {
            border-top: 1px solid #dee2e6;
            margin-top: 12px;
            padding-top: 12px;
            font-size: 14px;
        }

        .total-row strong {
            font-size: 21px;
        }

        .checkout-form {
            padding: 18px;
        }

        .checkout-form .form-label {
            font-size: 13px;
            margin-bottom: 6px;
        }

        .change-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding: 12px;
            border: 1px solid var(--pos-border);
            border-radius: 7px;
            font-size: 13px;
            background: #fff;
        }

        .change-box strong {
            font-size: 16px;
        }

        .qris-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .qris-info>i {
            font-size: 25px;
        }

        .qris-info strong {
            font-size: 13px;
        }

        .qris-info p {
            font-size: 12px;
            margin: 5px 0;
        }

        .qris-info small {
            color: var(--pos-muted);
            font-size: 11px;
        }

        .checkout-button {
            min-height: 45px;
            margin-top: 18px;
        }

        .checkout-button:disabled {
            opacity: .6;
        }

        .pending-payment {
            padding: 14px;
            border: 1px solid #f1d28a;
            background: #fff8e5;
            border-radius: 8px;
            font-size: 13px;
        }

        .pending-payment strong {
            display: block;
            margin-bottom: 5px;
        }

        .pending-payment button {
            margin-top: 10px;
        }

        @media (max-width: 1200px) {
            .pos-layout {
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 14px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .pos-layout {
                grid-template-columns: 1fr;
            }

            .pos-cart-column {
                min-width: 0;
            }

            .cart-items {
                max-height: 300px;
            }
        }

        @media (max-width: 575px) {
            .pos-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .pos-branch {
                width: 100%;
            }

            .catalog-toolbar {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px;
                padding: 0 12px 12px;
            }

            .product-image {
                height: 100px;
            }

            .product-info {
                padding: 9px;
            }

            .product-name {
                font-size: 13px;
            }

            .catalog-summary {
                padding: 10px 12px;
            }

            .cart-heading,
            .checkout-form {
                padding: 14px;
            }

            .cart-items {
                padding: 4px 14px;
            }

            .total-row strong {
                font-size: 18px;
            }

            .product-image {
                height: 150px;
                background: #f8f9fa;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .product-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .product-image .image-placeholder {
                font-size: 32px;
                color: #adb5bd;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
            }

        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productGrid = document.getElementById('productGrid');
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const branchSelect = document.getElementById('branchSelect');
            const productCount = document.getElementById('productCount');
            const cartItems = document.getElementById('cartItems');
            const cartCount = document.getElementById('cartCount');
            const summaryItems = document.getElementById('summaryItems');
            const grandTotal = document.getElementById('grandTotal');
            const paymentMethod = document.getElementById('paymentMethod');
            const cashReceived = document.getElementById('cashReceived');
            const changeAmount = document.getElementById('changeAmount');
            const cashSection = document.getElementById('cashSection');
            const qrisSection = document.getElementById('qrisSection');
            const checkoutButton = document.getElementById('checkoutButton');
            const checkoutMessage = document.getElementById('checkoutMessage');

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const productsUrl = @json(route('pos.products'));
            const checkoutUrl = @json(route('pos.checkout'));
            const confirmUrlTemplate = @json(route('pos.confirm-payment', ['sale' => '__SALE_ID__']));

            let products = [];
            let cart = [];
            let activeSaleId = null;
            let activeBranchId = branchSelect ? branchSelect.value : @json($selectedBranchId);

            function formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(Number(value) || 0);
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[char];
                });
            }

            function showMessage(message, type = 'danger') {
                checkoutMessage.innerHTML =
                    '<div class="alert alert-' + type + ' py-2 mb-0" role="alert">' +
                    escapeHtml(message) + '</div>';
            }

            async function readResponse(response) {
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : '';

                    throw new Error(errors || data.message || 'Permintaan gagal diproses.');
                }

                return data;
            }


            function getBranchQuery() {
                const params = new URLSearchParams();

                if (activeBranchId) {
                    params.set('branch_id', activeBranchId);
                }

                return params.toString();
            }


            async function loadProducts() {
                if (branchSelect && !branchSelect.value) {
                    products = [];
                    productCount.textContent = 'Pilih cabang';
                    return;
                }

                try {
                    const response = await fetch(productsUrl + '?' + getBranchQuery(), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });

                    const data = await readResponse(response);
                    products = data.products || [];

                    renderProducts();
                    renderCategories();
                } catch (error) {
                    productGrid.innerHTML =
                        '<div class="catalog-message text-danger">' +
                        escapeHtml(error.message) + '</div>';
                    productCount.textContent = 'Gagal memuat produk';
                }
            }

            function renderCategories() {
                const currentCategory = categoryFilter.value;
                const categories = [...new Set(
                    products.map(product => product.category).filter(Boolean)
                )].sort((a, b) => a.localeCompare(b, 'id'));

                categoryFilter.innerHTML = '<option value="">Semua kategori</option>';

                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categoryFilter.appendChild(option);
                });

                if (categories.includes(currentCategory)) {
                    categoryFilter.value = currentCategory;
                }
            }


            function getVisibleProducts() {
                const search = searchInput.value.trim().toLocaleLowerCase('id');
                const category = categoryFilter.value;

                return products
                    .filter(product => {
                        return !category || product.category === category;
                    })
                    .map(product => {
                        const productName = (product.product_name || '')
                            .toLocaleLowerCase('id');

                        const productMatches = productName.includes(search);

                        const matchingVariants = (product.variants || []).filter(variant => {
                            const variantName = (variant.variant_name || '')
                                .toLocaleLowerCase('id');

                            return !search ||
                                productMatches ||
                                variantName.includes(search);
                        });

                        return {
                            ...product,
                            variants: matchingVariants
                        };
                    })
                    .filter(product => product.variants.length > 0);
            }

            function renderProducts() {
                const visibleProducts = getVisibleProducts();
                let variantCount = 0;

                visibleProducts.forEach(product => {
                    variantCount += (product.variants || []).length;
                });

                productCount.textContent = variantCount + ' varian produk';

                if (visibleProducts.length === 0) {
                    productGrid.innerHTML =
                        '<div class="catalog-message">Produk tidak ditemukan.</div>';
                    return;
                }

                productGrid.innerHTML = visibleProducts.map(product => {
                    const variants = product.variants || [];

                    if (!variants.length) return '';

                    return variants.map(variant => {
                        const stock = Number(variant.stock) || 0;
                        const price = Number(variant.price) || 0;
                        const canAdd = stock > 0 && price > 0;
                        const cartItem = cart.find(item => item.variant_id === variant.variant_id);
                        const cartQuantity = cartItem ? cartItem.quantity : 0;
                        const remainingStock = Math.max(0, stock - cartQuantity);

                        let disabledReason = '';
                        if (price <= 0) disabledReason = 'Harga belum ditetapkan';
                        else if (remainingStock <= 0) disabledReason = 'Stok habis';

                        const image = variant.image
                            ? `<img src="/storage/product_variants/${encodeURIComponent(variant.image)}"
                                                                                                                                            alt="${escapeHtml(product.product_name)}"
                                                                                                                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                                                                                                           <i class="bi bi-basket image-placeholder"
                                                                                                                                            style="display:none"></i>`
                            : `<i class="bi bi-basket image-placeholder"></i>`;


                        return `
                                                                                                                                                                                                <article class="product-card">
                                                                                                                                                                                                    <div class="product-image">${image}</div>
                                                                                                                                                                                                    <div class="product-info">
                                                                                                                                                                                                        <div class="product-category">${escapeHtml(product.category)}</div>
                                                                                                                                                                                                        <div class="product-name">${escapeHtml(product.product_name)}</div>
                                                                                                                                                                                                        <div class="product-variant">${escapeHtml(variant.variant_name)}</div>
                                                                                                                                                                                                        <div class="product-price">${formatRupiah(price)}</div>
                                                                                                                                                                                                        <div class="product-stock ${remainingStock <= 0 ? 'out' : ''}">
                                                                                                                                                                                                            Stok: ${remainingStock}
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                        ${disabledReason ? `<div class="text-danger mt-1" style="font-size:11px">${disabledReason}</div>` : ''}
                                                                                                                                                                                                        <button type="button"
                                                                                                                                                                                                            class="btn btn-sm ${canAdd && remainingStock > 0 ? 'btn-outline-dark' : 'btn-outline-secondary'} add-product"
                                                                                                                                                                                                            data-variant-id="${variant.variant_id}"
                                                                                                                                                                                                            ${!canAdd || remainingStock <= 0 ? 'disabled' : ''}>
                                                                                                                                                                                                            <i class="bi bi-plus-lg me-1"></i>Tambah
                                                                                                                                                                                                        </button>
                                                                                                                                                                                                    </div>
                                                                                                                                                                                                </article>`;
                    }).join('');
                }).join('');
            }

            function renderCart() {
                const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);
                const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

                cartCount.textContent = totalQuantity + ' item';
                summaryItems.textContent = totalQuantity;
                grandTotal.textContent = formatRupiah(total);

                if (cart.length === 0) {
                    cartItems.innerHTML = `
                                                                                                                                                                                            <div class="empty-cart">
                                                                                                                                                                                                <i class="bi bi-cart3"></i>
                                                                                                                                                                                                <p>Keranjang masih kosong</p>
                                                                                                                                                                                                <span>Pilih produk untuk memulai transaksi.</span>
                                                                                                                                                                                            </div>`;
                } else {
                    cartItems.innerHTML = cart.map(item => `
                                                                                                                                                                                            <div class="cart-item">
                                                                                                                                                                                                <div>
                                                                                                                                                                                                    <div class="cart-item-name">${escapeHtml(item.product_name)} - ${escapeHtml(item.variant_name)}</div>
                                                                                                                                                                                                    <div class="cart-item-price">${formatRupiah(item.price)} / unit</div>
                                                                                                                                                                                                </div>
                                                                                                                                                                                                <div>
                                                                                                                                                                                                    <div class="cart-item-subtotal">${formatRupiah(item.price * item.quantity)}</div>
                                                                                                                                                                                                    <div class="quantity-control">
                                                                                                                                                                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                                                                                                                                                            data-action="decrease" data-id="${item.variant_id}"
                                                                                                                                                                                                            aria-label="Kurangi jumlah">−</button>
                                                                                                                                                                                                        <span class="quantity-value">${item.quantity}</span>
                                                                                                                                                                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                                                                                                                                                            data-action="increase" data-id="${item.variant_id}"
                                                                                                                                                                                                            aria-label="Tambah jumlah">+</button>
                                                                                                                                                                                                        <button type="button" class="remove-item"
                                                                                                                                                                                                            data-action="remove" data-id="${item.variant_id}"
                                                                                                                                                                                                            aria-label="Hapus item">
                                                                                                                                                                                                            <i class="bi bi-trash"></i>
                                                                                                                                                                                                        </button>
                                                                                                                                                                                                    </div>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            </div>`).join('');
                }

                const totalAmount = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
                const cash = Number(cashReceived.value) || 0;

                changeAmount.textContent = formatRupiah(
                    paymentMethod.value === 'cash' ? Math.max(0, cash - totalAmount) : 0
                );

                checkoutButton.disabled = cart.length === 0 || Boolean(activeSaleId);
                renderProducts();
            }

            function addToCart(variantId) {
                const product = products.find(item =>
                    (item.variants || []).some(variant => Number(variant.variant_id) === Number(variantId))
                );

                if (!product) return;

                const variant = product.variants.find(item =>
                    Number(item.variant_id) === Number(variantId)
                );

                if (!variant || Number(variant.price) <= 0 || Number(variant.stock) <= 0) {
                    showMessage('Varian ini belum memiliki harga atau stok yang tersedia.');
                    return;
                }

                const existing = cart.find(item => item.variant_id === Number(variantId));

                if (existing) {
                    if (existing.quantity + 1 > Number(variant.stock)) {
                        showMessage('Jumlah melebihi stok yang tersedia.');
                        return;
                    }
                    existing.quantity++;
                } else {
                    cart.push({
                        variant_id: Number(variant.variant_id),
                        product_name: product.product_name,
                        variant_name: variant.variant_name,
                        price: Number(variant.price),
                        stock: Number(variant.stock),
                        quantity: 1
                    });
                }

                checkoutMessage.innerHTML = '';
                renderCart();
            }

            productGrid.addEventListener('click', function (event) {
                const button = event.target.closest('[data-variant-id]');
                if (button && !button.disabled) {
                    addToCart(Number(button.dataset.variantId));
                }
            });

            cartItems.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action]');
                if (!button) return;

                const id = Number(button.dataset.id);
                const item = cart.find(row => row.variant_id === id);
                if (!item) return;

                if (button.dataset.action === 'increase') {
                    if (item.quantity >= item.stock) {
                        showMessage('Jumlah melebihi stok yang tersedia.');
                        return;
                    }
                    item.quantity++;
                } else if (button.dataset.action === 'decrease') {
                    item.quantity--;
                    if (item.quantity <= 0) {
                        cart = cart.filter(row => row.variant_id !== id);
                    }
                } else if (button.dataset.action === 'remove') {
                    cart = cart.filter(row => row.variant_id !== id);
                }

                checkoutMessage.innerHTML = '';
                renderCart();
            });

            document.getElementById('clearCart').addEventListener('click', function () {
                if (cart.length && !confirm('Kosongkan semua item di keranjang?')) return;
                cart = [];
                renderCart();
            });

            function updatePaymentView() {
                const isCash = paymentMethod.value === 'cash';

                if (cashSection) {
                    cashSection.hidden = !isCash;
                }

                if (qrisSection) {
                    qrisSection.hidden = isCash;
                }

                if (cashReceived) {
                    cashReceived.required = isCash;
                }

                renderCart();
            }

            cashReceived.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                renderCart();
            });

            paymentMethod.addEventListener('change', updatePaymentView);
            searchInput.addEventListener('input', renderProducts);
            categoryFilter.addEventListener('change', renderProducts);

            if (branchSelect) {
                branchSelect.addEventListener('change', async function () {
                    const newBranchId = branchSelect.value;

                    try {
                        const response = await fetch('/pos/select-branch', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                branch_id: newBranchId
                            })
                        });

                        await readResponse(response);

                        activeBranchId = newBranchId;
                        cart = [];
                        activeSaleId = null;
                        checkoutMessage.innerHTML = '';

                        await loadProducts();
                        renderCart();

                    } catch (error) {
                        showMessage(error.message);
                        branchSelect.value = activeBranchId;
                    }
                });
            }

            checkoutButton.addEventListener('click', async function () {
                if (cart.length === 0) {
                    showMessage('Keranjang masih kosong.');
                    return;
                }

                if (branchSelect && !branchSelect.value) {
                    showMessage('Pilih cabang terlebih dahulu.');
                    return;
                }

                const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
                const method = paymentMethod.value;
                const cash = Number(cashReceived.value) || 0;

                if (method === 'cash' && cash < total) {
                    showMessage('Uang yang diterima kurang dari total pembayaran.');
                    return;
                }

                const payload = {
                    payment_method: method,
                    items: cart.map(item => ({
                        variant_id: item.variant_id,
                        quantity: item.quantity
                    }))
                };

                if (activeBranchId) payload.branch_id = Number(activeBranchId);
                if (method === 'cash') payload.cash_received = cash;

                checkoutButton.disabled = true;
                checkoutButton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

                try {
                    const response = await fetch(checkoutUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await readResponse(response);

                    if (data.payment_status === 'pending') {
                        activeSaleId = data.sale_id;
                        showMessage('Transaksi #' + data.sale_id +
                            ' menunggu konfirmasi pembayaran QRIS.', 'warning');

                        checkoutMessage.innerHTML += `
                                                                                                                                                                                                <div class="pending-payment mt-2">
                                                                                                                                                                                                    <strong>Menunggu pembayaran QRIS</strong>
                                                                                                                                                                                                    Total: ${formatRupiah(data.total_amount)}
                                                                                                                                                                                                    <p class="mb-0 mt-2">
                                                                                                                                                                                                        Pastikan pembayaran benar-benar diterima sebelum konfirmasi.
                                                                                                                                                                                                    </p>
                                                                                                                                                                                                    <button type="button" id="confirmQrisButton"
                                                                                                                                                                                                        class="btn btn-success btn-sm w-100">
                                                                                                                                                                                                        Konfirmasi pembayaran diterima
                                                                                                                                                                                                    </button>
                                                                                                                                                                                                </div>`;

                        document.getElementById('confirmQrisButton')
                            .addEventListener('click', confirmQrisPayment);

                        return;
                    }

                    showMessage(
                        'Pembayaran berhasil! Kembalian: ' +
                        formatRupiah(data.change_amount),
                        'success'
                    );

                    cart = [];
                    cashReceived.value = '';
                    await loadProducts();
                    renderCart();
                } catch (error) {
                    showMessage(error.message);
                } finally {
                    checkoutButton.textContent = 'Proses pembayaran';
                    if (!activeSaleId) checkoutButton.disabled = cart.length === 0;
                }
            });

            async function confirmQrisPayment() {
                if (!activeSaleId) return;

                const button = document.getElementById('confirmQrisButton');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Mengonfirmasi...';
                }

                try {
                    const url = confirmUrlTemplate.replace('__SALE_ID__', activeSaleId);

                    const response = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const data = await readResponse(response);

                    showMessage('Pembayaran transaksi #' + data.sale_id +
                        ' dikonfirmasi. Stok produk telah diperbarui.', 'success');

                    activeSaleId = null;
                    cart = [];
                    cashReceived.value = '';
                    await loadProducts();
                    renderCart();
                } catch (error) {
                    showMessage(error.message);
                    const retryButton = document.getElementById('confirmQrisButton');
                    if (retryButton) {
                        retryButton.disabled = false;
                        retryButton.textContent = 'Coba konfirmasi lagi';
                    }
                }
            }

            updatePaymentView();
            loadProducts();
        });
    </script>
@endsection