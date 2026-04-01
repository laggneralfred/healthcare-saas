<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('practitioners')->nullOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained('appointment_types')->nullOnDelete();
            $table->foreignId('message_template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('send_at_offset_minutes');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['practice_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_rules');
    }
};
