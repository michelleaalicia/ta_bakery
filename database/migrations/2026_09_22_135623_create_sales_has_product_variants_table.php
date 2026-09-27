<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_has_product_variants', function (Blueprint $table) {
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);

            $table->primary(['sale_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_has_product_variants');
    }
};
