<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->string('format');              // 'csv' or 'json'
            $table->string('file_path')->nullable();
            $table->string('status')->default('processing'); // processing | ready | downloaded | expired | failed
            $table->timestamp('expires_at');
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['practice_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_tokens');
    }
};
