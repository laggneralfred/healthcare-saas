<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_appointment_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained('appointment_types')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['practitioner_id', 'appointment_type_id']);
            $table->index(['practice_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_appointment_type');
    }
};
