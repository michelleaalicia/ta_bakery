<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ingredients
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();
        });

        // Isi tenant_id berdasarkan branch yang sudah ada
        DB::statement('
            UPDATE ingredients
            INNER JOIN branches ON ingredients.branch_id = branches.id
            SET ingredients.tenant_id = branches.tenant_id
        ');

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->change();
        });


        // Production Orders
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->change();
        });


        // Sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->change();
        });


        // Forecasts
        Schema::table('forecasts', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('forecasts', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');

            $table->foreignId('branch_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable(false)
                ->change();

            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};