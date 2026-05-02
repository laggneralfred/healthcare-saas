<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_portal_tokens', function (Blueprint $table) {
            $table->foreignId('new_patient_interest_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('new_patient_interests')
                ->nullOnDelete();

            $table->index(['practice_id', 'new_patient_interest_id', 'purpose'], 'portal_tokens_interest_purpose_index');
        });
    }

    public function down(): void
    {
        Schema::table('patient_portal_tokens', function (Blueprint $table) {
            $table->dropIndex('portal_tokens_interest_purpose_index');
            $table->dropConstrainedForeignId('new_patient_interest_id');
        });
    }
};
