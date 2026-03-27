<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_email')->nullable();
            $table->string('action');                 // viewed|created|updated|deleted|state_changed|signed|exported
            $table->string('auditable_type');         // model class
            $table->unsignedBigInteger('auditable_id');
            $table->string('auditable_label');        // human-readable, e.g. "John Smith"
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('metadata')->nullable();    // extra context (e.g. tender_type, from_state)
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — audit logs are immutable

            $table->index(['auditable_type', 'auditable_id'], 'activity_logs_auditable_index');
            $table->index(['practice_id', 'created_at'], 'activity_logs_practice_created_index');
            $table->index('action', 'activity_logs_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
