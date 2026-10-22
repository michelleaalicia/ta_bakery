<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_order_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('custom_order_id')
                ->constrained('custom_orders')
                ->cascadeOnDelete();

            $table->foreignId('ingredient_master_id')
                ->constrained('ingredient_masters');

            $table->decimal('quantity', 15, 3);
            $table->string('unit', 45);

            $table->decimal('estimated_unit_cost', 15, 2)->default(0);
            $table->decimal('estimated_total_cost', 15, 2)->default(0);

            $table->timestamps();

            $table->unique([
                'custom_order_id',
                'ingredient_master_id'
            ], 'custom_order_unique_material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_materials');
    }
};
