@php
    $modules = auth()->user()->role
        ? auth()->user()->role->modules->pluck('name')
        : collect();
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Sistem Bakery' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-700">

    <div class="min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col">

            {{-- Logo --}}
            <div class="h-16 flex items-center px-5 border-b border-gray-200">
                <div class="w-8 h-8 border border-gray-300 flex items-center justify-center mr-3">
                    <span class="text-xs text-gray-400">Logo</span>
                </div>

                <span class="font-semibold text-gray-900">
                    Sistem Bakery
                </span>
            </div>


            {{-- Menu --}}
            <nav class="flex-1 px-3 py-5 overflow-y-auto">

                {{-- MASTER DATA --}}
                <p class="px-3 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Master Data
                </p>

                @if($modules->contains('Dashboard'))
                    <a href="{{ url('/dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Dashboard
                    </a>
                @endif

                @if($modules->contains('Manajemen Pengguna'))
                    <a href="{{ url('/manajemen-pengguna') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Manajemen Pengguna
                    </a>
                @endif

                @if($modules->contains('Cabang'))
                    <a href="{{ url('/cabang') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Cabang
                    </a>
                @endif

                @if($modules->contains('Kategori Produk'))
                    <a href="{{ url('/kategori-produk') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Kategori Produk
                    </a>
                @endif

                @if($modules->contains('Produk'))
                    <a href="{{ url('/produk') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Produk
                    </a>
                @endif

                @if($modules->contains('Bahan Baku'))
                    <a href="{{ url('/bahan-baku') }}"
                        class="flex items-center gap-3 px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Bahan Baku
                    </a>
                @endif


                {{-- PRODUKSI --}}
                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Produksi
                </p>

                @if($modules->contains('Resep'))
                    <a href="{{ url('/resep') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Resep
                    </a>
                @endif

                @if($modules->contains('Produksi'))
                    <a href="{{ url('/produksi') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Produksi
                    </a>
                @endif

                @if($modules->contains('Jadwal Produksi'))
                    <a href="{{ url('/jadwal-produksi') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Jadwal Produksi
                    </a>
                @endif


                {{-- PESANAN --}}
                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Pesanan
                </p>

                @if($modules->contains('Custom Order'))
                    <a href="{{ url('/custom-order') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Custom Order
                    </a>
                @endif

                @if($modules->contains('Pembayaran'))
                    <a href="{{ url('/pembayaran') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Pembayaran
                    </a>
                @endif


                {{-- PENJUALAN --}}
                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Penjualan
                </p>

                @if($modules->contains('POS'))
                    <a href="{{ url('/pos') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        POS
                    </a>
                @endif

                @if($modules->contains('Riwayat Penjualan'))
                    <a href="{{ url('/riwayat-penjualan') }}"
                        class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Riwayat Penjualan
                    </a>
                @endif


                {{-- LAPORAN --}}
                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Laporan
                </p>

                @if($modules->contains('Laporan Penjualan'))
                    <a href="{{ url('/laporan-penjualan') }}"
                        class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Laporan Penjualan
                    </a>
                @endif

                @if($modules->contains('Laporan Laba Rugi'))
                    <a href="{{ url('/laporan-laba-rugi') }}"
                        class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Laporan Laba Rugi
                    </a>
                @endif

                @if($modules->contains('Forecast'))
                    <a href="{{ url('/forecast') }}" class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Forecast
                    </a>
                @endif


                {{-- SISTEM --}}
                <p class="px-3 mt-6 mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                    Sistem
                </p>

                @if($modules->contains('Riwayat Aktivitas'))
                    <a href="{{ url('/riwayat-aktivitas') }}"
                        class="block px-3 py-2 mb-1 text-sm rounded hover:bg-gray-100">
                        Riwayat Aktivitas
                    </a>
                @endif

            </nav>


            {{-- Bottom --}}
            <div class="border-t border-gray-200 p-3">

                <div class="px-3 py-2 text-sm text-gray-600">
                    {{ auth()->user()->name }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="w-full text-left px-3 py-2 text-sm text-gray-600 rounded hover:bg-gray-100">
                        Logout
                    </button>
                </form>

            </div>

        </aside>


        {{-- CONTENT --}}
        <main class="flex-1 min-w-0">

            {{-- Header --}}
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8">
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ $title ?? 'Dashboard' }}
                </h1>

                <span class="text-sm text-gray-500">
                    {{ auth()->user()->name }}
                </span>
            </header>

            {{-- Page --}}
            <div class="p-8">
                {{ $slot }}
            </div>

        </main>

    </div>

</body>

</html>