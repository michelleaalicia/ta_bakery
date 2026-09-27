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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('order_type', ['regular', 'custom']);
            $table->decimal('quantity', 10, 2);
            $table->date('production_date');
            $table->enum('status', [
                'planned',
                'in_progress',
                'completed',
                'cancelled'
            ]);
            $table->decimal('bop_cost', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
