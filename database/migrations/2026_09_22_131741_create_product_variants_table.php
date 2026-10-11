<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variant_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->decimal('stock', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['product_variant_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_stocks');
    }
};