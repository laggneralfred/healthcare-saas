<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->foreignId('appointment_type_id')
                ->nullable()
                ->after('requested_service')
                ->constrained('appointment_types')
                ->nullOnDelete();
            $table->foreignId('practitioner_id')
                ->nullable()
                ->after('appointment_type_id')
                ->constrained('practitioners')
                ->nullOnDelete();

            $table->index(['practice_id', 'appointment_type_id']);
            $table->index(['practice_id', 'practitioner_id']);
        });
    }

    public function down(): void
    {
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->dropIndex(['practice_id', 'appointment_type_id']);
            $table->dropIndex(['practice_id', 'practitioner_id']);
            $table->dropConstrainedForeignId('appointment_type_id');
            $table->dropConstrainedForeignId('practitioner_id');
        });
    }
};
