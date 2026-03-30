<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();

            // Status lifecycle: pending → analyzing → ready → importing → complete | failed
            $table->string('status')->default('pending');

            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();

            $table->json('detected_headers')->nullable();
            $table->json('column_mappings')->nullable(); // [index => field_key]

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);

            $table->json('dry_run_results')->nullable(); // {valid:[...], errors:[...], duplicates:[...]}
            $table->string('error_report_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['practice_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
