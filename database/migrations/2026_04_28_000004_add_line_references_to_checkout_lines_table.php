<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_lines', function (Blueprint $table) {
            $table->foreignId('service_fee_id')
                ->nullable()
                ->after('practice_id')
                ->constrained('service_fees')
                ->nullOnDelete();
            $table->string('line_type')
                ->default('custom')
                ->after('service_fee_id');
            $table->decimal('unit_price', 10, 2)
                ->nullable()
                ->after('amount');
        });

        DB::table('checkout_lines')
            ->whereNotNull('inventory_product_id')
            ->update(['line_type' => 'inventory']);

        DB::table('checkout_lines')
            ->whereNull('inventory_product_id')
            ->update(['line_type' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('checkout_lines', function (Blueprint $table) {
            $table->dropForeign(['service_fee_id']);
            $table->dropColumn(['service_fee_id', 'line_type', 'unit_price']);
        });
    }
};
