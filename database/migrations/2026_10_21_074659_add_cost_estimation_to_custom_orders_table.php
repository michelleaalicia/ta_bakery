<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->decimal('estimated_labor_hours', 10, 2)->nullable();
            $table->decimal('estimated_labor_rate', 15, 2)->nullable();
            $table->decimal('estimated_overhead_rate', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_labor_hours',
                'estimated_labor_rate',
                'estimated_overhead_rate',
            ]);
        });
    }
};
