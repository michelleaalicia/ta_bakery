<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Bakery </title>
    <meta name="description"
        content="Sistem informasi untuk mengelola bahan baku, resep, produksi, HPP, dan penjualan bakery.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #1f2420;
            --muted: #7a7f77;
            --line: #ececE6;
            --bg: #ffffff;
            --soft: #f7f6f2;
        }

        * {
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        /* Navbar */
        .site-nav {
            padding: 20px 0;
        }

        .site-nav .logo {
            height: 36px;
            width: auto;
        }

        .site-nav .brand-text {
            font-weight: 600;
            font-size: 16px;
            letter-spacing: -.2px;
            color: var(--ink);
        }

        .nav-text-link {
            color: var(--muted);
            font-size: 14px;
            text-decoration: none;
            transition: color .15s;
        }

        .nav-text-link:hover {
            color: var(--ink);
        }

        /* Buttons */
        .btn-ink {
            background: var(--ink);
            color: #fff;
            border: 1px solid var(--ink);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            padding: 11px 22px;
            transition: background .15s;
        }

        .btn-ink:hover {
            background: #000;
            color: #fff;
        }

        .btn-ghost {
            background: transparent;
            color: var(--ink);
            border: 1px solid #d9d9d2;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            padding: 11px 22px;
            transition: border-color .15s;
        }

        .btn-ghost:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        /* Hero */
        .hero {
            padding: 110px 0 100px;
            text-align: center;
        }

        .hero-logo {
            height: 72px;
            width: auto;
            margin-bottom: 36px;
        }

        .hero h1 {
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 600;
            letter-spacing: -2px;
            line-height: 1.08;
            margin: 0 auto 22px;
            max-width: 760px;
        }

        .hero p {
            font-size: 17px;
            color: var(--muted);
            line-height: 1.7;
            max-width: 460px;
            margin: 0 auto 36px;
        }

        /* Features */
        .features {
            border-top: 1px solid var(--line);
            padding: 90px 0;
        }

        .features .label {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .features h2 {
            font-size: clamp(26px, 3.5vw, 34px);
            font-weight: 600;
            letter-spacing: -1px;
            line-height: 1.2;
            max-width: 320px;
        }

        .feature-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 24px;
            padding: 20px 0;
            border-bottom: 1px solid var(--line);
        }

        .feature-row:first-child {
            padding-top: 0;
        }

        .feature-row .name {
            font-size: 16px;
            font-weight: 500;
        }

        .feature-row .desc {
            font-size: 14px;
            color: var(--muted);
            text-align: right;
        }

        /* CTA */
        .cta {
            background: var(--soft);
            padding: 90px 0;
            text-align: center;
        }

        .cta h2 {
            font-size: clamp(26px, 4vw, 38px);
            font-weight: 600;
            letter-spacing: -1.2px;
            margin-bottom: 28px;
        }

        /* Footer */
        footer {
            padding: 28px 0;
            font-size: 13px;
            color: var(--muted);
        }

        @media (max-width: 767px) {
            .hero {
                padding: 70px 0 70px;
            }

            .features {
                padding: 64px 0;
            }

            .cta {
                padding: 64px 0;
            }

            .feature-row {
                flex-direction: column;
                gap: 4px;
            }

            .feature-row .desc {
                text-align: left;
            }
        }
    </style>
</head>

<body>

    <nav class="site-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ url('/') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('images/bakery-logo.png') }}" alt="Logo SIM Bakery" class="logo">
                <span class="brand-text">SIM Bakery</span>
            </a>

            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('login') }}" class="nav-text-link">Login</a>
                <a href="{{ route('register') }}" class="btn btn-ink btn-sm">Daftar</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container">
                <img src="{{ asset('images/bakery-logo.png') }}" alt="SIM Bakery" class="hero-logo">

                <h1>Kelola bakery,<br>tanpa ribet.</h1>

                <p>Dari bahan baku sampai penjualan, semua tercatat rapi dalam satu sistem.</p>

            </div>
        </section>

        <section class="features" id="fitur">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <div class="label">Fitur</div>
                        <h2>Semua yang dibutuhkan bakery.</h2>
                    </div>

                    <div class="col-lg-8">
                        <div class="feature-row">
                            <span class="name">Bahan baku &amp; stok</span>
                            <span class="desc">Pantau persediaan dan restock</span>
                        </div>
                        <div class="feature-row">
                            <span class="name">Resep &amp; produksi</span>
                            <span class="desc">Takaran, batch, dan tenaga kerja</span>
                        </div>
                        <div class="feature-row">
                            <span class="name">HPP &amp; harga jual</span>
                            <span class="desc">Biaya per unit otomatis terhitung</span>
                        </div>
                        <div class="feature-row">
                            <span class="name">Penjualan &amp; pesanan</span>
                            <span class="desc">POS dan pesanan khusus</span>
                        </div>
                        <div class="feature-row">
                            <span class="name">Laporan &amp; forecasting</span>
                            <span class="desc">Penjualan, laba rugi, dan prediksi</span>
                        </div>
                        <div class="feature-row">
                            <span class="name">Multi cabang &amp; pengguna</span>
                            <span class="desc">Atur cabang, peran, dan hak akses</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta" id="tentang">
            <div class="container">
                <h2>Siap merapikan operasional bakery?</h2>
                <a href="{{ route('register') }}" class="btn btn-ink">Daftar Sebagai Owner</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>© {{ date('Y') }} SIM Bakery</span>
            <a href="{{ route('login') }}" class="nav-text-link">Login</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>