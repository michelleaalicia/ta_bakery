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
        Schema::create('users_has_production_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('production_order_id')->constrained('production_orders');
            $table->string('job_description');
            $table->enum('status', [
                'assigned',
                'in_progress',
                'completed'
            ]);
            $table->decimal('labor_hours', 8, 2);
            $table->decimal('labor_rate_per_hour', 15, 2);
            $table->timestamps();

            $table->primary(['user_id', 'production_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_has_production_orders');
    }
};
