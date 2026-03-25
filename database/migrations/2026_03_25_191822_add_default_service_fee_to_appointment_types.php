<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->foreignId('default_service_fee_id')
                ->nullable()
                ->constrained('service_fees')
                ->nullOnDelete()
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->dropForeign(['default_service_fee_id']);
            $table->dropColumn('default_service_fee_id');
        });
    }
};
