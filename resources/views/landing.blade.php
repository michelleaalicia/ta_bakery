<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Bakery - Sistem Informasi Manajemen Bakery</title>
    <meta name="description"
        content="Sistem informasi untuk membantu pengelolaan bahan baku, resep, produksi, biaya, dan penjualan bakery.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --dark: #343a35;
            --dark-hover: #252a26;
            --cream: #f6f4ef;
            --muted: #73776f;
            --line: #e7e5df;
        }

        body {
            font-family: Arial, sans-serif;
            color: var(--dark);
            background: #fff;
        }

        .navbar {
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--line);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--dark);
            color: white;
            font-size: 21px;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--dark);
        }

        .brand-subtitle {
            font-size: 11px;
            color: var(--muted);
        }

        .nav-link {
            color: #555b55;
            font-size: 14px;
        }

        .nav-link:hover {
            color: var(--dark);
        }

        .btn-dark-custom {
            color: white;
            background: var(--dark);
            border: 1px solid var(--dark);
        }

        .btn-dark-custom:hover {
            color: white;
            background: var(--dark-hover);
        }

        .btn-outline-custom {
            color: var(--dark);
            border: 1px solid #c9cbc4;
            background: white;
        }

        .btn-outline-custom:hover {
            color: var(--dark);
            background: var(--cream);
            border-color: var(--dark);
        }

        .hero {
            background:
                radial-gradient(ellipse at 90% 10%, #e9e5d9 0, transparent 35%),
                var(--cream);
            padding: 90px 0 85px;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 13px;
            background: #e8e7df;
            color: #555c50;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .3px;
        }

        .hero h1 {
            font-size: clamp(34px, 5vw, 54px);
            line-height: 1.15;
            letter-spacing: -1.5px;
            font-weight: 750;
            margin: 22px 0;
        }

        .hero p {
            max-width: 570px;
            line-height: 1.8;
            color: #686d65;
            font-size: 16px;
        }

        .hero-panel {
            background: white;
            border: 1px solid #e8e5dd;
            border-radius: 22px;
            padding: 27px;
            box-shadow: 0 15px 45px rgba(43, 46, 39, .06);
        }

        .hero-panel .panel-heading {
            font-size: 13px;
            color: var(--muted);
        }

        .feature-preview {
            padding: 17px;
            background: #faf9f6;
            border: 1px solid #efede7;
            border-radius: 13px;
            height: 100%;
        }

        .preview-icon {
            width: 39px;
            height: 39px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #e9e9e1;
            font-size: 19px;
            margin-bottom: 13px;
        }

        .preview-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .preview-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
        }

        .section {
            padding: 80px 0;
        }

        .section-label {
            color: #777c72;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.3px;
        }

        .section-title {
            font-size: clamp(27px, 4vw, 36px);
            letter-spacing: -.7px;
            font-weight: 700;
            margin: 12px 0;
        }

        .section-description {
            color: var(--muted);
            line-height: 1.8;
            max-width: 620px;
            margin: 0 auto;
        }

        .feature-card {
            height: 100%;
            padding: 25px;
            border: 1px solid var(--line);
            border-radius: 16px;
            transition: transform .2s, box-shadow .2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(40, 43, 37, .06);
        }

        .feature-card h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 17px 0 9px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
            margin: 0;
        }

        .feature-icon {
            width: 47px;
            height: 47px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream);
            font-size: 22px;
        }

        .workflow {
            background: var(--cream);
        }

        .workflow-item {
            padding: 22px;
            background: white;
            border: 1px solid var(--line);
            border-radius: 15px;
            height: 100%;
        }

        .step-number {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--dark);
            color: white;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 17px;
        }

        .cta {
            background: var(--dark);
            color: white;
            border-radius: 22px;
            padding: 48px 25px;
        }

        .cta p {
            color: #d0d3cc;
            line-height: 1.8;
        }

        footer {
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
        }

        section {
            scroll-margin-top: 85px;
        }

        @media (max-width: 767px) {
            .hero {
                padding: 58px 0;
            }

            .hero h1 {
                letter-spacing: -.8px;
            }

            .section {
                padding: 60px 0;
            }

            .hero-panel {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container py-2">
            <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center gap-2">
                <span class="brand-icon">
                    <i class="bi bi-cake2"></i>
                </span>
                <span>
                    <span class="brand-name d-block">SIM Bakery</span>
                    <span class="brand-subtitle d-block">Sistem Informasi Manajemen Bakery</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3 mt-3 mt-lg-0">
                    <a href="#fitur" class="nav-link">Fitur</a>
                    <a href="#alur" class="nav-link">Alur Sistem</a>
                    <a href="#tentang" class="nav-link">Tentang</a>

                    <a href="{{ route('login') }}" class="btn btn-outline-custom btn-sm px-3 mt-2 mt-lg-0">
                        Login
                    </a>

                    <a href="{{ route('register') }}" class="btn btn-dark-custom btn-sm px-3 mt-2 mt-lg-0">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7">
                        <span class="eyebrow">
                            <i class="bi bi-grid-1x2 me-1"></i>
                            Pengelolaan Bakery dalam Satu Sistem
                        </span>

                        <h1>
                            Kelola Produksi Bakery
                            <span class="d-block">Lebih Terstruktur.</span>
                        </h1>

                        <p>
                            Kelola bahan baku, resep, proses produksi, biaya,
                            hingga penjualan melalui satu sistem informasi
                            yang dirancang untuk membantu operasional bakery.
                        </p>

                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a href="{{ route('register') }}" class="btn btn-dark-custom px-4 py-3">
                                Daftar
                                <i class="bi bi-arrow-right ms-2"></i>
                            </a>

                            <a href="{{ route('login') }}" class="btn btn-outline-custom px-4 py-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Login
                            </a>
                        </div>

                        <div class="d-flex flex-wrap gap-4 mt-4 text-secondary" style="font-size: 12px;">
                            <span><i class="bi bi-check-circle me-1"></i> Data lebih terorganisasi</span>
                            <span><i class="bi bi-check-circle me-1"></i> Perhitungan biaya produksi</span>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="hero-panel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <div class="panel-heading">Gambaran Sistem</div>
                                    <h5 class="fw-bold mt-1 mb-0">Operasional Bakery</h5>
                                </div>
                                <i class="bi bi-bar-chart-line fs-3"></i>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="feature-preview">
                                        <div class="preview-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="preview-title">Bahan Baku</div>
                                        <div class="preview-desc">Stok dan pencatatan restock</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="feature-preview">
                                        <div class="preview-icon">
                                            <i class="bi bi-journal-text"></i>
                                        </div>
                                        <div class="preview-title">Resep</div>
                                        <div class="preview-desc">Bahan, takaran, dan waktu produksi</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="feature-preview">
                                        <div class="preview-icon">
                                            <i class="bi bi-gear"></i>
                                        </div>
                                        <div class="preview-title">Produksi</div>
                                        <div class="preview-desc">Batch, tenaga kerja, dan biaya</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="feature-preview">
                                        <div class="preview-icon">
                                            <i class="bi bi-calculator"></i>
                                        </div>
                                        <div class="preview-title">HPP dan Harga</div>
                                        <div class="preview-desc">Dasar perhitungan harga jual</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 p-3 rounded-3" style="background: #f3f2ed;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle fs-5"></i>
                                    <span style="font-size: 12px; line-height: 1.6;">
                                        Data produksi dan bahan baku terhubung
                                        untuk mendukung pencatatan operasional.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="fitur">
            <div class="container">
                <div class="text-center mb-5">
                    <div class="section-label">Fitur Sistem</div>
                    <h2 class="section-title">Semua Kebutuhan Bakery dalam Satu Tempat</h2>
                    <p class="section-description">
                        Fitur untuk membantu pengelolaan data, pencatatan produksi,
                        penghitungan biaya, serta pemantauan aktivitas usaha bakery.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-people"></i></div>
                            <h3>Manajemen Pengguna</h3>
                            <p>Kelola akun pengguna, peran, hak akses, dan pekerjaan karyawan sesuai kebutuhan
                                operasional.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-shop"></i></div>
                            <h3>Manajemen Cabang</h3>
                            <p>Kelola data cabang dan sesuaikan pencatatan operasional dengan lokasi bakery.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                            <h3>Produk dan Bahan Baku</h3>
                            <p>Atur kategori, produk, varian, stok bahan, serta pencatatan penambahan persediaan.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-journal-text"></i></div>
                            <h3>Resep Produk</h3>
                            <p>Catat takaran bahan, satuan hasil resep, langkah pembuatan, dan waktu produksi.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-gear"></i></div>
                            <h3>Manajemen Produksi</h3>
                            <p>Rencanakan produksi berdasarkan batch, catat penggunaan bahan baku, tenaga kerja, dan
                                biaya overhead.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-calculator"></i></div>
                            <h3>Perhitungan HPP</h3>
                            <p>Hitung biaya produksi per unit sebagai dasar penentuan harga jual dengan persentase
                                keuntungan.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-cart3"></i></div>
                            <h3>Penjualan dan Pesanan</h3>
                            <p>Dukung pencatatan penjualan melalui POS serta pengelolaan pesanan khusus pelanggan.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
                            <h3>Laporan dan Analisis</h3>
                            <p>Pantau laporan penjualan dan laba rugi, serta gunakan forecasting untuk membantu
                                perencanaan produk reguler.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                            <h3>Riwayat Aktivitas</h3>
                            <p>Telusuri catatan aktivitas sistem untuk membantu pemantauan perubahan data dan aktivitas
                                pengguna.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section workflow" id="alur">
            <div class="container">
                <div class="text-center mb-5">
                    <div class="section-label">Alur Pengelolaan</div>
                    <h2 class="section-title">Dari Persiapan hingga Penjualan</h2>
                    <p class="section-description">
                        Data dikelola secara bertahap untuk mendukung pencatatan
                        operasional bakery.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <div class="workflow-item">
                            <div class="step-number">1</div>
                            <h6 class="fw-bold">Kelola Data</h6>
                            <p class="small text-secondary mb-0">
                                Siapkan data produk, varian, bahan baku, resep, dan karyawan.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="workflow-item">
                            <div class="step-number">2</div>
                            <h6 class="fw-bold">Rencanakan Produksi</h6>
                            <p class="small text-secondary mb-0">
                                Tentukan jumlah batch, jadwal, kebutuhan bahan, dan tenaga kerja.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="workflow-item">
                            <div class="step-number">3</div>
                            <h6 class="fw-bold">Hitung Biaya</h6>
                            <p class="small text-secondary mb-0">
                                Catat biaya bahan baku, tenaga kerja, overhead, HPP, dan harga jual.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="workflow-item">
                            <div class="step-number">4</div>
                            <h6 class="fw-bold">Pantau Hasil</h6>
                            <p class="small text-secondary mb-0">
                                Kelola penjualan, persediaan produk jadi, laporan, dan perencanaan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="tentang">
            <div class="container">
                <div class="cta text-center">
                    <div class="section-label" style="color: #d4d8ce;">Mulai Menggunakan Sistem</div>
                    <h2 class="section-title mt-3">Kelola Bakery dengan Lebih Terorganisasi</h2>
                    <p class="mx-auto mb-4" style="max-width: 620px;">
                        Daftarkan usaha bakery sebagai Owner untuk mulai mengelola
                        data dan aktivitas operasional melalui sistem.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('register') }}" class="btn btn-light px-4 py-3">
                            Daftar
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>

                        <a href="{{ route('login') }}" class="btn btn-outline-light px-4 py-3">
                            Saya Sudah Punya Akun
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-4">
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>© {{ date('Y') }} Sistem Informasi Manajemen Bakery</span>
            <span>Mendukung pengelolaan operasional bakery.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>