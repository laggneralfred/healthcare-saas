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
        Schema::create('acupuncture_encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->unique()->constrained('encounters')->cascadeOnDelete();
            $table->string('tcm_diagnosis')->nullable();
            $table->text('points_used')->nullable();
            $table->text('meridians')->nullable();
            $table->text('treatment_protocol')->nullable();
            $table->unsignedSmallInteger('needle_count')->nullable();
            $table->text('session_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acupuncture_encounters');
    }
};
