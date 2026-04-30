<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->text('pulse_before_treatment')->nullable()->after('pulse_quality');
            $table->text('pulse_after_treatment')->nullable()->after('pulse_before_treatment');
            $table->text('pulse_change_interpretation')->nullable()->after('pulse_after_treatment');
        });
    }

    public function down(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->dropColumn([
                'pulse_before_treatment',
                'pulse_after_treatment',
                'pulse_change_interpretation',
            ]);
        });
    }
};
