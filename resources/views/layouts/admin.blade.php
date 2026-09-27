<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Sistem Bakery')</title>

    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
        .app-sidebar .nav-link,
        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-link:focus,
        .app-sidebar .nav-link:active {
            color: #000000 !important;
            background-color: transparent !important;
        }

        .app-sidebar .nav-link .nav-icon,
        .app-sidebar .nav-link:hover .nav-icon,
        .app-sidebar .nav-link:focus .nav-icon,
        .app-sidebar .nav-link:active .nav-icon {
            color: #000000 !important;
        }

        .app-sidebar .nav-header {
            color: #000000 !important;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.8px;
            padding: 12px 8px 6px;
        }

        .app-sidebar .nav-link.active {
            background-color: rgba(0, 0, 0, 0.06) !important;
            color: #000000 !important;
            font-weight: 600;
        }

        .app-sidebar .nav-link.active .nav-icon {
            color: #000000 !important;
            font-weight: 600;
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary" data-bs-theme="light">

    <div class="app-wrapper">

        {{-- Navbar --}}
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#">
                            ☰
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            {{ auth()->user()->name }}
                        </span>
                    </li>
                </ul>

            </div>
        </nav>

        {{-- Sidebar --}}
        @php
            $modules = auth()->user()->role
                ? auth()->user()->role->modules->pluck('name')
                : collect();
        @endphp

        <aside class="app-sidebar bg-body-tertiary shadow" data-bs-theme="light">

            <div class="sidebar-brand">
                <a href="{{ route('dashboard') }}" class="brand-link">
                    <span class="brand-text fw-light">
                        Sistem Bakery
                    </span>
                </a>
            </div>

            <div class="sidebar-wrapper">

                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

                        {{-- DASHBOARD --}}
                        @if ($modules->contains('Dashboard'))
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}"
                                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-grid"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                        @endif


                        {{-- MASTER DATA --}}
                        @if (
                                $modules->contains('Manajemen Pengguna') ||
                                $modules->contains('Cabang') ||
                                $modules->contains('Kategori Produk') ||
                                $modules->contains('Produk') ||
                                $modules->contains('Bahan Baku')

                            )
                            <li class="nav-header">MASTER DATA</li>
                        @endif

                        @if ($modules->contains('Manajemen Pengguna'))
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}"
                                    class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-people"></i>
                                    <p>Manajemen Pengguna</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Cabang'))
                            <li class="nav-item">
                                <a href="{{ route('branches.index') }}"
                                    class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-shop"></i>
                                    <p>Cabang</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Kategori Produk'))
                            <li class="nav-item">
                                <a href="{{ route('categories.index') }}"
                                    class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-tag"></i>
                                    <p>Kategori Produk</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Produk'))
                            <li class="nav-item">
                                <a href="{{ route('products.index') }}"
                                    class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-box"></i>
                                    <p>Produk</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Bahan Baku'))
                            <li class="nav-item">
                                <a href="{{ route('ingredients.index') }}"
                                    class="nav-link {{ request()->routeIs('ingredients.*') ? 'active' : '' }}">

                                    <i class="nav-icon bi bi-flask"></i>

                                    <p>Bahan Baku</p>

                                </a>
                            </li>
                        @endif




                        {{-- PRODUKSI --}}
                        @if (
                                $modules->contains('Resep') ||
                                $modules->contains('Produksi') ||
                                $modules->contains('Jadwal Produksi')
                            )
                            <li class="nav-header">PRODUKSI</li>
                        @endif
                        @if ($modules->contains('Resep'))
                            <li class="nav-item">
                                <a href="{{ route('recipes.index') }}"
                                    class="nav-link {{ request()->routeIs('recipes.*') ? 'active' : '' }}">

                                    <i class="nav-icon bi bi-journal-text"></i>
                                    <p>Resep</p>

                                </a>
                            </li>
                        @endif
                        @if ($modules->contains('Produksi'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-bar-chart-steps"></i>
                                    <p>Produksi</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Jadwal Produksi'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-calendar3"></i>
                                    <p>Jadwal Produksi</p>
                                </a>
                            </li>
                        @endif


                        {{-- PESANAN --}}
                        @if (
                                $modules->contains('Custom Order') ||
                                $modules->contains('Pembayaran')
                            )
                            <li class="nav-header">PESANAN</li>
                        @endif

                        @if ($modules->contains('Custom Order'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-clipboard"></i>
                                    <p>Custom Order</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Pembayaran'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-credit-card"></i>
                                    <p>Pembayaran</p>
                                </a>
                            </li>
                        @endif


                        {{-- PENJUALAN --}}
                        @if (
                                $modules->contains('POS') ||
                                $modules->contains('Riwayat Penjualan')
                            )
                            <li class="nav-header">PENJUALAN</li>
                        @endif

                        @if ($modules->contains('POS'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-cart3"></i>
                                    <p>POS</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Riwayat Penjualan'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-arrow-counterclockwise"></i>
                                    <p>Riwayat Penjualan</p>
                                </a>
                            </li>
                        @endif


                        {{-- LAPORAN --}}
                        @if (
                                $modules->contains('Laporan Penjualan') ||
                                $modules->contains('Laporan Laba Rugi') ||
                                $modules->contains('Forecast')
                            )
                            <li class="nav-header">LAPORAN</li>
                        @endif

                        @if ($modules->contains('Laporan Penjualan'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-bar-chart-line"></i>
                                    <p>Laporan Penjualan</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Laporan Laba Rugi'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-graph-up"></i>
                                    <p>Laporan Laba Rugi</p>
                                </a>
                            </li>
                        @endif

                        @if ($modules->contains('Forecast'))
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-graph-up-arrow"></i>
                                    <p>Forecast</p>
                                </a>
                            </li>
                        @endif


                        {{-- SISTEM --}}
                        @if ($modules->contains('Riwayat Aktivitas'))
                            <li class="nav-header">SISTEM</li>

                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-activity"></i>
                                    <p>Riwayat Aktivitas</p>
                                </a>
                            </li>
                        @endif

                        {{-- AKUN --}}
                        <li class="nav-header">AKUN</li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-person"></i>
                                <p>Profil</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start">
                                    <i class="nav-icon bi bi-box-arrow-right"></i>
                                    <p>Logout</p>
                                </button>
                            </form>
                        </li>

                    </ul>
                </nav>

            </div>
        </aside>


        {{-- Main Content --}}
        <main class="app-main">

            <div class="app-content-header">
                <div class="container-fluid">
                    <h3 class="mb-0">
                        @yield('page-title', 'Dashboard')
                    </h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">

                    @yield('content')

                </div>
            </div>

        </main>

    </div>

    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>

</body>

</html>