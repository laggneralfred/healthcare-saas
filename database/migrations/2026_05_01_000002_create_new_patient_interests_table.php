<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_patient_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('preferred_service')->nullable();
            $table->foreignId('preferred_practitioner_id')->nullable()->constrained('practitioners')->nullOnDelete();
            $table->text('preferred_days_times')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->timestamps();

            $table->index(['practice_id', 'status']);
            $table->index(['practice_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_patient_interests');
    }
};
