<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('ingredient_master_id')->nullable()
                ->constrained('ingredient_masters');
        });

        // Gunakan nama+satuan untuk menyatukan bahan dari cabang berbeda.
        // Bila nama atau satuan tidak konsisten, perbaiki secara manual sebelum migrasi.
        foreach (DB::table('ingredients')->orderBy('id')->get() as $ingredient) {
            $master = DB::table('ingredient_masters')
                ->where('tenant_id', $ingredient->tenant_id)
                ->where('name', $ingredient->name)
                ->where('unit', $ingredient->unit)
                ->first();
            $id = $master?->id ?? DB::table('ingredient_masters')->insertGetId([
                'tenant_id' => $ingredient->tenant_id,
                'name' => $ingredient->name,
                'unit' => $ingredient->unit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('ingredients')->where('id', $ingredient->id)
                ->update(['ingredient_master_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingredient_master_id');
        });
        Schema::dropIfExists('ingredient_masters');
    }
};
