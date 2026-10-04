<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('profit_percentage', 5, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'profit_percentage',
                'selling_price',
            ]);
        });
    }
};