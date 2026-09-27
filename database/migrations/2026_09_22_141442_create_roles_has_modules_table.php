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
        Schema::create('roles_has_modules', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('module_id')->constrained('modules');

            $table->primary(['role_id', 'module_id']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_has_modules');
    }
};
