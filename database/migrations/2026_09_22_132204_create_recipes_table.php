<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 45);
            $table->decimal('production_time', 8, 2);
            $table->text('steps');
            $table->timestamps();

            $table->unique('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
