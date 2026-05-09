<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('practice_name')->nullable();
            $table->string('profession')->nullable();
            $table->string('practice_type')->nullable();
            $table->string('heard_about_us')->nullable();
            $table->string('source')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_up_at');
            $table->timestamps();

            $table->index(['signed_up_at', 'practice_id']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_signups');
    }
};
