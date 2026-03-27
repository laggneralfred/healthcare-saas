<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE: This migration deliberately extends plain Migration, not SafeMigration.
 * SafeMigration logs to this table; if the table did not yet exist we would
 * have a chicken-and-egg problem.  All subsequent migrations should extend
 * App\Database\SafeMigration instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migrations_log', function (Blueprint $table) {
            $table->id();
            $table->string('migration');                 // filename stem, e.g. "2026_03_26_000006_add_index"
            $table->string('direction', 10);             // 'up' | 'down'
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->boolean('success')->nullable();      // null while in-flight
            $table->text('error')->nullable();

            $table->index(['migration', 'direction'], 'migrations_log_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migrations_log');
    }
};
