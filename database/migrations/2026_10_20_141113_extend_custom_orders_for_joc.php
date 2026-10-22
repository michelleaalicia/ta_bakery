<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->foreignId('production_order_id')
                ->nullable()
                ->change();

            $table->foreignId('tenant_id')
                ->constrained('tenants');

            $table->foreignId('branch_id')
                ->constrained('branches');

            $table->string('order_number', 30)->unique();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->date('required_date');
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->string('status', 30)->default('draft');
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['order_number']);

            $table->dropColumn([
                'tenant_id',
                'branch_id',
                'order_number',
                'product_name',
                'quantity',
                'required_date',
                'selling_price',
                'status',
            ]);

            $table->foreignId('production_order_id')
                ->nullable(false)
                ->change();
        });
    }
};
