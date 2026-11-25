<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FAF8F5">
    <meta name="description"
        content="SIM Bakery membantu mengelola stok bahan baku, resep, produksi, biaya, penjualan, dan laporan dalam satu sistem.">
    <link rel="icon" type="image/png" href="{{ asset('images/bakery-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght,SOFT,WONK@9..144,400,20,0;9..144,500,20,0;9..144,600,20,0&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing-redesign.css') }}?v=2">
</head>

<body>
    {{-- Dekorasi ikon SVG ringan, tanpa dependency JavaScript. --}}
    <svg class="svg-defs" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="i-box" viewBox="0 0 24 24">
            <path d="m3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10" />
        </symbol>
        <symbol id="i-book" viewBox="0 0 24 24">
            <path d="M4 5.5c3-1.5 5.5-1 8 1v14c-2.5-2-5-2.5-8-1V5.5Zm16 0c-3-1.5-5.5-1-8 1v14c2.5-2 5-2.5 8-1V5.5Z" />
        </symbol>
        <symbol id="i-calc" viewBox="0 0 24 24">
            <rect x="5" y="2.5" width="14" height="19" rx="2" />
            <path d="M8 7h8M8 11h2m4 0h2m-8 4h2m4 0h2m-8 4h2m4 0h2" />
        </symbol>
        <symbol id="i-bag" viewBox="0 0 24 24">
            <path d="M4 8h16l-1 13H5L4 8Zm4 0V6a4 4 0 0 1 8 0v2" />
        </symbol>
        <symbol id="i-chart" viewBox="0 0 24 24">
            <path d="M3 3v18h18M7 16l4-5 3 2 5-7" />
        </symbol>
        <symbol id="i-users" viewBox="0 0 24 24">
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5" />
        </symbol>
        <symbol id="i-arrow" viewBox="0 0 24 24">
            <path d="M4 12h16m-7-7 7 7-7 7" />
        </symbol>
        <symbol id="i-check" viewBox="0 0 24 24">
            <path d="m4 12 5 5L20 6" />
        </symbol>
        <symbol id="i-layers" viewBox="0 0 24 24">
            <path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5m-18 5 9 5 9-5" />
        </symbol>
        <symbol id="i-clock" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </symbol>
        <symbol id="i-menu" viewBox="0 0 24 24">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </symbol>
        <symbol id="i-close" viewBox="0 0 24 24">
            <path d="M5 5 19 19M19 5 5 19" />
        </symbol>
    </svg>

    <header class="site-header" id="atas">
        <div class="wrap nav-shell">
            <a href="{{ url('/') }}" class="brand" aria-label="SIM Bakery, ke halaman utama">
                <img src="{{ asset('images/bakery-logo.png') }}" alt="" width="42" height="42">
                <span><strong>SIM Bakery</strong></span> </a>
            <button class="menu-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false"
                aria-controls="site-menu">
                <svg class="ico" aria-hidden="true">
                    <use href="#i-menu" />
                </svg>
            </button>
            <nav id="site-menu" class="nav-links" aria-label="Navigasi utama">
                <a href="{{ route('login') }}">Masuk</a>
                <a href="{{ route('register') }}" class="button button-primary button-small">Daftar </a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero section-light">
            <div class="wrap hero-grid">
                <div class="hero-copy">
                    <h1>Urusan bakery<br><em>lebih mudah</em><br>dari sini.</h1>
                    <p>Stok bahan baku, resep, produksi, sampai penjualan. Kelola semuanya dalam satu sistem, tanpa
                        perlu berpindah-pindah catatan.</p>
                </div>
                <div class="hero-visual" aria-label="Ilustrasi antarmuka manajemen SIM Bakery">
                    <div class="visual-orbit orbit-one" aria-hidden="true"></div>
                    <div class="visual-orbit orbit-two" aria-hidden="true"></div>
                    <div class="hero-asterisk" aria-hidden="true">✳</div>
                    <div class="mock-browser hero-browser">
                        <div class="browser-top"><span class="browser-dots"><i></i><i></i><i></i></span><span
                                class="browser-url">SIM Bakery / Ringkasan Operasional</span><span
                                class="browser-top-action">•••</span></div>
                        <div class="mock-app">
                            <aside class="mock-sidebar" aria-hidden="true">
                                <div class="mock-s-brand"><img src="{{ asset('images/bakery-logo.png') }}" alt=""> SIM
                                    Bakery</div>
                                <div class="mock-nav selected"><svg class="ico">
                                        <use href="#i-layers" />
                                    </svg> Dashboard</div>
                                <div class="mock-nav"><svg class="ico">
                                        <use href="#i-box" />
                                    </svg> Bahan Baku</div>
                                <div class="mock-nav"><svg class="ico">
                                        <use href="#i-book" />
                                    </svg> Resep</div>
                                <div class="mock-nav"><svg class="ico">
                                        <use href="#i-calc" />
                                    </svg> Produksi</div>
                                <div class="mock-nav"><svg class="ico">
                                        <use href="#i-chart" />
                                    </svg> Laporan</div>
                            </aside>
                            <div class="mock-content">
                                <div class="mock-heading"><span><strong>Ringkasan Operasional</strong><small>Pantau
                                            kegiatan bakery dalam satu tempat</small></span><span class="mock-chip">Hari
                                        ini</span></div>
                                <div class="mock-stats">
                                    <div><svg class="ico">
                                            <use href="#i-box" />
                                        </svg><small>Stok bahan baku</small><strong>Pantau stok</strong><span>Persediaan
                                            tercatat</span></div>
                                    <div><svg class="ico">
                                            <use href="#i-clock" />
                                        </svg><small>Aktivitas produksi</small><strong>Pantau
                                            proses</strong><span>Status produksi</span></div>
                                </div>
                                <div class="mock-board">
                                    <div class="mock-board-heading"><strong>Alur operasional</strong><small>Ringkasan
                                            modul</small></div>
                                    <div class="mock-board-row"><span>Bahan baku</span><i class="track"><b
                                                style="width: 78%"></b></i></div>
                                    <div class="mock-board-row"><span>Produksi</span><i class="track"><b
                                                style="width: 54%"></b></i></div>
                                    <div class="mock-board-row"><span>Penjualan</span><i class="track"><b
                                                style="width: 67%"></b></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <section class="problem section-cream" aria-labelledby="problem-title">
            <div class="wrap problem-layout">
                <div class="problem-intro">
                    <span class="eyebrow">KENAPA SIM BAKERY?</span>
                    <h2 id="problem-title">
                        SIM Bakery bisa kelola banyak hal,
                        <em>dalam satu tempat!</em>
                    </h2>
                </div>

                <div class="problem-story">
                    <p>
                        Mulai dari persediaan bahan, biaya produksi, hingga
                        penjualan. Semua informasi yang dibutuhkan untuk
                        mengelola bakery tersusun rapi dan mudah diakses.
                    </p>

                </div>
            </div>
        </section>


        <section class="features section-light" id="fitur" aria-labelledby="features-title">
            <div class="wrap">
                <div class="section-heading feature-heading">
                    <div><span class="eyebrow"></span>
                        <h2 id="features-title">Satu sistem,<br><em>untuk seluruh operasional bakery.</em></h2>
                    </div>
                </div>
                <div class="bento-grid">
                    <article class="feature-card feature-stock">
                        <div class="feature-top">
                            <span class="feature-number">01</span>
                        </div>
                        <h3>Bahan Baku &amp; Stok</h3>
                        <p>Tahu apa yang tersedia, apa yang mulai menipis, dan kapan perlu restock.</p>
                        <div class="stock-illustration" aria-hidden="true">
                            <div class="ui-mini-title">Persediaan bahan</div>
                            <div class="stock-line"><span>Tepung terigu</span><i><b style="width:75%"></b></i></div>
                            <div class="stock-line"><span>Gula pasir</span><i><b style="width:48%"></b></i></div>
                            <div class="stock-line"><span>Mentega</span><i><b style="width:62%"></b></i></div>
                        </div>
                    </article>
                    <article class="feature-card feature-production">
                        <div class="feature-top">
                            <span class="feature-number">02</span>
                        </div>
                        <h3>Resep &amp; Produksi</h3>
                        <p>Kelola resep, takaran bahan, dan proses produksi dengan lebih terstruktur.</p>
                        <div class="recipe-illustration" aria-hidden="true"><span
                                class="recipe-sheet sheet-back"></span>
                            <div class="recipe-sheet sheet-front"><span><svg class="ico">
                                        <use href="#i-book" />
                                    </svg> Lembar produksi</span><i></i><i></i><i class="short"></i><small>RESEP · BAHAN
                                    · PROSES</small></div>
                        </div>
                    </article>
                    <article class="feature-card feature-cost">
                        <div class="feature-top">
                            <span class="feature-number">03</span>
                        </div>
                        <div class="cost-copy">
                            <h3>HPP &amp; Harga Jual</h3>
                            <p>Hitung biaya produksi per produk sebagai dasar menentukan harga jual.</p>
                        </div>
                        <div class="cost-illustration" aria-hidden="true">
                            <div><span>Bahan baku</span><b class="cost-line long"></b></div>
                            <div><span>Tenaga kerja</span><b class="cost-line mid"></b></div>
                            <div><span>Overhead</span><b class="cost-line short"></b></div>
                            <div class="cost-total"><span>Biaya produksi</span><strong>BBB + BTKL + BOP</strong></div>
                        </div>
                    </article>
                    <article class="feature-card feature-simple">
                        <div class="feature-top">
                            <span class="feature-number">04</span>
                        </div>
                        <h3>Penjualan &amp; Pesanan</h3>
                        <p>Catat transaksi dan pantau pesanan tanpa kehilangan detail.</p>
                    </article>
                    <article class="feature-card feature-simple">
                        <div class="feature-top">
                            <span class="feature-number">05</span>
                        </div>
                        <h3>Laporan &amp; Forecasting</h3>
                        <p>Lihat perkembangan usaha dan gunakan data untuk membantu perencanaan berikutnya.</p>
                    </article>
                    <article class="feature-card feature-simple">
                        <div class="feature-top">
                            <span class="feature-number">06</span>
                        </div>
                        <h3>Multi-cabang &amp; Pengguna</h3>
                        <p>Kelola akses pengguna dan operasional beberapa cabang dalam satu sistem.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="final-section" aria-labelledby="final-title">
            <div class="wrap">
                <div class="final-card">
                    <div class="final-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
                    <h2 id="final-title">Mulai kelola bakery<br>dengan <em>lebih sederhana.</em></h2><a
                        href="{{ route('register') }}" class="button button-cream">Daftar Sebagai Owner </a>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.querySelector('.menu-toggle');
            const menu = document.getElementById('site-menu');
            if (!toggle || !menu) return;
            toggle.addEventListener('click', function () {
                const open = menu.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
            });
            menu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    menu.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.setAttribute('aria-label', 'Buka navigasi');
                });
            });
        });
    </script>
</body>

</html>