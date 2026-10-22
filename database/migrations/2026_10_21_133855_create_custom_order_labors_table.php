<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_order_labors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('custom_order_id')
                ->constrained('custom_orders')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users');

            $table->string('job_description');

            $table->decimal('estimated_hours', 10, 2);
            $table->decimal('estimated_rate', 15, 2);
            $table->decimal('estimated_cost', 15, 2);

            $table->timestamps();

            $table->unique(['custom_order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_labors');
    }
};
