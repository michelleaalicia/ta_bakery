<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variant_stocks', function (Blueprint $table) {
            $table->decimal('average_unit_cost', 15, 2)->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_stocks', function (Blueprint $table) {
            $table->dropColumn('average_unit_cost');
        });
    }
};
