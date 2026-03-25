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
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('state')->default('draft');
            $table->string('charge_label');
            $table->decimal('amount_total', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('tender_type')->nullable(); // cash | card
            $table->timestamp('started_on')->nullable();
            $table->timestamp('paid_on')->nullable();
            $table->string('payment_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
