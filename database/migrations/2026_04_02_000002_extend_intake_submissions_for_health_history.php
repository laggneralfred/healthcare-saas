<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_submissions', function (Blueprint $table) {
            // Discipline & chief complaint
            $table->string('discipline')->nullable()->after('summary_text');
            $table->text('chief_complaint')->nullable()->after('discipline');
            $table->string('onset_duration')->nullable()->after('chief_complaint');
            $table->string('onset_type')->nullable()->after('onset_duration');
            $table->text('aggravating_factors')->nullable()->after('onset_type');
            $table->text('relieving_factors')->nullable()->after('aggravating_factors');
            $table->unsignedTinyInteger('pain_scale')->nullable()->after('relieving_factors');
            $table->boolean('previous_episodes')->default(false)->after('pain_scale');
            $table->text('previous_episodes_description')->nullable()->after('previous_episodes');

            // Medical history (JSONB arrays)
            $table->jsonb('current_medications')->nullable()->after('previous_episodes_description');
            $table->jsonb('allergies')->nullable()->after('current_medications');
            $table->jsonb('past_diagnoses')->nullable()->after('allergies');
            $table->jsonb('past_surgeries')->nullable()->after('past_diagnoses');

            // Health flags
            $table->boolean('is_pregnant')->default(false)->after('past_surgeries');
            $table->boolean('has_pacemaker')->default(false)->after('is_pregnant');
            $table->boolean('takes_blood_thinners')->default(false)->after('has_pacemaker');
            $table->boolean('has_bleeding_disorder')->default(false)->after('takes_blood_thinners');
            $table->boolean('has_infectious_disease')->default(false)->after('has_bleeding_disorder');

            // Lifestyle
            $table->string('exercise_frequency')->nullable()->after('has_infectious_disease');
            $table->string('sleep_quality')->nullable()->after('exercise_frequency');
            $table->unsignedTinyInteger('sleep_hours')->nullable()->after('sleep_quality');
            $table->string('stress_level')->nullable()->after('sleep_hours');
            $table->text('diet_description')->nullable()->after('stress_level');
            $table->string('smoking_status')->nullable()->after('diet_description');
            $table->string('smoking_amount')->nullable()->after('smoking_status');
            $table->string('alcohol_use')->nullable()->after('smoking_amount');

            // Previous treatment
            $table->boolean('had_previous_treatment')->default(false)->after('alcohol_use');
            $table->jsonb('previous_treatments_tried')->nullable()->after('had_previous_treatment');
            $table->text('previous_treatment_results')->nullable()->after('previous_treatments_tried');
            $table->boolean('other_practitioner')->default(false)->after('previous_treatment_results');
            $table->string('other_practitioner_name')->nullable()->after('other_practitioner');

            // Goals
            $table->text('treatment_goals')->nullable()->after('other_practitioner_name');
            $table->text('success_indicators')->nullable()->after('treatment_goals');

            // Discipline-specific flexible responses
            $table->jsonb('discipline_responses')->nullable()->after('success_indicators');

            // Consent
            $table->boolean('consent_given')->default(false)->after('discipline_responses');
            $table->dateTime('consent_signed_at')->nullable()->after('consent_given');
            $table->string('consent_signed_by')->nullable()->after('consent_signed_at');
            $table->string('consent_ip_address')->nullable()->after('consent_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('intake_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'discipline', 'chief_complaint', 'onset_duration', 'onset_type',
                'aggravating_factors', 'relieving_factors', 'pain_scale',
                'previous_episodes', 'previous_episodes_description',
                'current_medications', 'allergies', 'past_diagnoses', 'past_surgeries',
                'is_pregnant', 'has_pacemaker', 'takes_blood_thinners',
                'has_bleeding_disorder', 'has_infectious_disease',
                'exercise_frequency', 'sleep_quality', 'sleep_hours',
                'stress_level', 'diet_description', 'smoking_status', 'smoking_amount',
                'alcohol_use', 'had_previous_treatment', 'previous_treatments_tried',
                'previous_treatment_results', 'other_practitioner', 'other_practitioner_name',
                'treatment_goals', 'success_indicators', 'discipline_responses',
                'consent_given', 'consent_signed_at', 'consent_signed_by', 'consent_ip_address',
            ]);
        });
    }
};
