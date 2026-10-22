<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users_has_production_orders', function (Blueprint $table) {
            $table->decimal('labor_rate_per_hour', 15, 2)->nullable();
        });

        // Data lama tidak memiliki snapshot upah. Isi dengan tarif saat migrasi
        // agar tidak berubah lagi setelah ini (bukan tarif historis pasti).
        DB::table('users_has_production_orders')
            ->join('users', 'users.id', '=', 'users_has_production_orders.user_id')
            ->update(['users_has_production_orders.labor_rate_per_hour' => DB::raw('users.wage_rate_per_hour')]);
    }

    public function down(): void
    {
        Schema::table('users_has_production_orders', function (Blueprint $table) {
            $table->dropColumn('labor_rate_per_hour');
        });
    }
};
