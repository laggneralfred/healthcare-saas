<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE checkout_sessions ALTER COLUMN appointment_id DROP NOT NULL');

        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->foreignId('encounter_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained()
                ->nullOnDelete();

            $table->unique('encounter_id');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropUnique(['encounter_id']);
            $table->dropConstrainedForeignId('encounter_id');
        });

        DB::statement('ALTER TABLE checkout_sessions ALTER COLUMN appointment_id SET NOT NULL');
    }
};
