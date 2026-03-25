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
        Schema::create('intake_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('missing'); // missing | complete
            $table->dateTime('submitted_on')->nullable();
            $table->string('access_token', 64)->unique();
            $table->text('reason_for_visit')->nullable();
            $table->text('current_concerns')->nullable();
            $table->text('relevant_history')->nullable();
            $table->text('medications')->nullable();
            $table->text('notes')->nullable();
            $table->text('summary_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_submissions');
    }
};
