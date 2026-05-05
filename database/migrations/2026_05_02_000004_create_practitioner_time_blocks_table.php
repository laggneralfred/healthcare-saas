<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_time_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('block_type')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['practice_id', 'practitioner_id', 'starts_at', 'ends_at'], 'practitioner_time_blocks_range_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_time_blocks');
    }
};
