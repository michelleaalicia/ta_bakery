<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->change();

            $table->string('payment_method', 20)
                ->default('cash');

            $table->string('payment_status', 20)
                ->default('paid');

            $table->timestamp('updated_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'updated_at',
            ]);
        });
    }
};
