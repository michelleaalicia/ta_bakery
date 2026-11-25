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
        Schema::create('custom_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders');
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('customer_name');
            $table->string('customer_contact', 50);
            $table->date('order_date');
            $table->text('specification');
            $table->string('order_number', 30)->unique();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->date('required_date');
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('estimated_labor_hours', 10, 2)->nullable();
            $table->decimal('estimated_labor_rate', 15, 2)->nullable();
            $table->decimal('estimated_overhead_rate', 15, 2)->nullable();
            $table->decimal('estimated_bop_cost', 15, 2)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_orders');
    }
};
