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
        Schema::create('checkout_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence')->default(0);
            $table->string('description');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_lines');
    }
};
