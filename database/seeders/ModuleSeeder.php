<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'Dashboard',
            'Manajemen Pengguna',
            'Cabang',
            'Kategori Produk',
            'Produk',
            'Bahan Baku',
            'Resep',
            'Produksi',
            'Jadwal Produksi',
            'Custom Order',
            'Pembayaran',
            'POS',
            'Riwayat Penjualan',
            'Laporan Penjualan',
            'Laporan Laba Rugi',
            'Forecast',
            'Riwayat Aktivitas',
        ];

        foreach ($modules as $module) {
            Module::firstOrCreate([
                'name' => $module,
            ]);
        }
    }
}