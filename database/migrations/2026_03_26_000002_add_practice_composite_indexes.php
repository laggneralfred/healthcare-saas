<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds composite indexes on every table that carries a practice_id column.
 *
 * Universal indexes (all 10 tables)
 * ──────────────────────────────────
 *   (practice_id, created_at)  — date-range list queries
 *   (practice_id, id)          — paginated detail lookups
 *
 * Domain-specific indexes
 * ───────────────────────
 *   appointments      : (practice_id, start_datetime)
 *                       (practice_id, status, start_datetime)
 *   practitioners     : (practice_id, is_active)
 *   patients          : (practice_id, name)
 *   checkout_sessions : (practice_id, state)
 *   encounters        : (practice_id, appointment_id)
 *
 * Column-name notes (actual schema vs. original requirement wording)
 * ──────────────────────────────────────────────────────────────────
 *   appointments.start_datetime  — requirement called it "scheduled_at"
 *   patients.name                — requirement called it "last_name"
 *   practitioners.is_active      — requirement called it "status"
 *   checkout_sessions.state      — requirement called it "status"
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── appointments ──────────────────────────────────────────────────────
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'],    'appointments_practice_created_at');
            $table->index(['practice_id', 'id'],            'appointments_practice_id');
            $table->index(['practice_id', 'start_datetime'],'appointments_practice_start_datetime');
            $table->index(
                ['practice_id', 'status', 'start_datetime'],
                'appointments_practice_status_start_datetime'
            );
        });

        // ── patients ──────────────────────────────────────────────────────────
        Schema::table('patients', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'patients_practice_created_at');
            $table->index(['practice_id', 'id'],         'patients_practice_id');
            $table->index(['practice_id', 'name'],       'patients_practice_name');
        });

        // ── practitioners ─────────────────────────────────────────────────────
        Schema::table('practitioners', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'practitioners_practice_created_at');
            $table->index(['practice_id', 'id'],         'practitioners_practice_id');
            $table->index(['practice_id', 'is_active'],  'practitioners_practice_is_active');
        });

        // ── encounters ────────────────────────────────────────────────────────
        Schema::table('encounters', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'],    'encounters_practice_created_at');
            $table->index(['practice_id', 'id'],            'encounters_practice_id');
            $table->index(['practice_id', 'appointment_id'],'encounters_practice_appointment_id');
        });

        // ── checkout_sessions ─────────────────────────────────────────────────
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'checkout_sessions_practice_created_at');
            $table->index(['practice_id', 'id'],         'checkout_sessions_practice_id');
            $table->index(['practice_id', 'state'],      'checkout_sessions_practice_state');
        });

        // ── checkout_lines ────────────────────────────────────────────────────
        Schema::table('checkout_lines', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'checkout_lines_practice_created_at');
            $table->index(['practice_id', 'id'],         'checkout_lines_practice_id');
        });

        // ── intake_submissions ────────────────────────────────────────────────
        Schema::table('intake_submissions', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'intake_submissions_practice_created_at');
            $table->index(['practice_id', 'id'],         'intake_submissions_practice_id');
        });

        // ── consent_records ───────────────────────────────────────────────────
        Schema::table('consent_records', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'consent_records_practice_created_at');
            $table->index(['practice_id', 'id'],         'consent_records_practice_id');
        });

        // ── service_fees ──────────────────────────────────────────────────────
        Schema::table('service_fees', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'service_fees_practice_created_at');
            $table->index(['practice_id', 'id'],         'service_fees_practice_id');
        });

        // ── appointment_types ─────────────────────────────────────────────────
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->index(['practice_id', 'created_at'], 'appointment_types_practice_created_at');
            $table->index(['practice_id', 'id'],         'appointment_types_practice_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_practice_created_at');
            $table->dropIndex('appointments_practice_id');
            $table->dropIndex('appointments_practice_start_datetime');
            $table->dropIndex('appointments_practice_status_start_datetime');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_practice_created_at');
            $table->dropIndex('patients_practice_id');
            $table->dropIndex('patients_practice_name');
        });

        Schema::table('practitioners', function (Blueprint $table) {
            $table->dropIndex('practitioners_practice_created_at');
            $table->dropIndex('practitioners_practice_id');
            $table->dropIndex('practitioners_practice_is_active');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropIndex('encounters_practice_created_at');
            $table->dropIndex('encounters_practice_id');
            $table->dropIndex('encounters_practice_appointment_id');
        });

        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropIndex('checkout_sessions_practice_created_at');
            $table->dropIndex('checkout_sessions_practice_id');
            $table->dropIndex('checkout_sessions_practice_state');
        });

        Schema::table('checkout_lines', function (Blueprint $table) {
            $table->dropIndex('checkout_lines_practice_created_at');
            $table->dropIndex('checkout_lines_practice_id');
        });

        Schema::table('intake_submissions', function (Blueprint $table) {
            $table->dropIndex('intake_submissions_practice_created_at');
            $table->dropIndex('intake_submissions_practice_id');
        });

        Schema::table('consent_records', function (Blueprint $table) {
            $table->dropIndex('consent_records_practice_created_at');
            $table->dropIndex('consent_records_practice_id');
        });

        Schema::table('service_fees', function (Blueprint $table) {
            $table->dropIndex('service_fees_practice_created_at');
            $table->dropIndex('service_fees_practice_id');
        });

        Schema::table('appointment_types', function (Blueprint $table) {
            $table->dropIndex('appointment_types_practice_created_at');
            $table->dropIndex('appointment_types_practice_id');
        });
    }
};
