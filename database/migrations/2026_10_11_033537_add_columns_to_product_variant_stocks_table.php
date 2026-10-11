<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variant_stocks', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->after('id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->decimal('stock', 10, 2)
                ->default(0)
                ->after('branch_id');

            $table->unique(
                ['product_variant_id', 'branch_id'],
                'variant_branch_stock_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_stocks', function (Blueprint $table) {
            $table->dropUnique('variant_branch_stock_unique');
            $table->dropForeign(['product_variant_id']);
            $table->dropForeign(['branch_id']);

            $table->dropColumn([
                'product_variant_id',
                'branch_id',
                'stock',
            ]);
        });
    }
};