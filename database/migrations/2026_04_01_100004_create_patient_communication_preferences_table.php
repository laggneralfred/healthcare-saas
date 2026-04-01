<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_communication_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->boolean('email_opt_in')->default(true);
            $table->boolean('sms_opt_in')->default(true);
            $table->string('preferred_channel')->default('email');
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();

            $table->unique(['practice_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_communication_preferences');
    }
};
