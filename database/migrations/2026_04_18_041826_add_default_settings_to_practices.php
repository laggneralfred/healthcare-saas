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
        Schema::table('practices', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_appointment_duration')->default(30)->after('timezone');
            $table->smallInteger('default_reminder_hours')->default(24)->after('default_appointment_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropColumn(['default_appointment_duration', 'default_reminder_hours']);
        });
    }
};
