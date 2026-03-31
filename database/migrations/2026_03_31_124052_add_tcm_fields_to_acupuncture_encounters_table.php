<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->string('tongue_body')->nullable();
            $table->string('tongue_coating')->nullable();
            $table->string('pulse_quality')->nullable();
            $table->string('zang_fu_diagnosis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->dropColumn(['tongue_body', 'tongue_coating', 'pulse_quality', 'zang_fu_diagnosis']);
        });
    }
};
