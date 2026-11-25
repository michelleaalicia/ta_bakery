<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('name', 45);
            $table->string('unit', 45);
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_masters');
    }
};
