<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users_has_production_orders', function (Blueprint $table) {
            $table->dropColumn('labor_rate_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('users_has_production_orders', function (Blueprint $table) {
            $table->decimal('labor_rate_per_hour', 15, 2)->default(0);
        });
    }
};