<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_review_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('practice_type')->nullable();
            $table->string('clinic_size')->nullable();
            $table->json('current_systems')->nullable();
            $table->text('first_impression')->nullable();
            $table->integer('setup_clarity_rating')->nullable();
            $table->string('setup_checklist_helpfulness')->nullable();
            $table->string('confusing_setup_step')->nullable();
            $table->string('website_links_feedback')->nullable();
            $table->string('scheduling_preference')->nullable();
            $table->string('online_forms_feedback')->nullable();
            $table->string('notes_workflow')->nullable();
            $table->string('ai_feedback')->nullable();
            $table->string('follow_up_feedback')->nullable();
            $table->string('pricing_feedback')->nullable();
            $table->text('subscription_blockers')->nullable();
            $table->text('most_useful')->nullable();
            $table->text('most_confusing')->nullable();
            $table->text('one_change')->nullable();
            $table->boolean('may_contact')->default(false);
            $table->string('contact_info')->nullable();
            $table->boolean('discount_acknowledged')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['practice_id', 'submitted_at']);
            $table->index(['user_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_review_submissions');
    }
};
