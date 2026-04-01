<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->json('five_elements')->nullable();
            $table->string('csor_color')->nullable();
            $table->string('csor_sound')->nullable();
            $table->string('csor_odor')->nullable();
            $table->string('csor_emotion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('acupuncture_encounters', function (Blueprint $table) {
            $table->dropColumn(['five_elements', 'csor_color', 'csor_sound', 'csor_odor', 'csor_emotion']);
        });
    }
};
