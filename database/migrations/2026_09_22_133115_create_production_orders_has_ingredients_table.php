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
        Schema::create('production_orders_has_ingredients', function (Blueprint $table) {
            $table->foreignId('production_order_id')
                ->constrained('production_orders');

            $table->foreignId('ingredient_id')
                ->constrained('ingredients');

            $table->decimal('quantity_used', 10, 2);
            $table->decimal('unit_cost_used', 10, 2);

            $table->primary(['production_order_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders_has_ingredients');
    }
};
