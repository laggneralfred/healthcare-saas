<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->text('chief_complaint')->nullable()->after('visit_date');
            $table->text('subjective')->nullable()->after('chief_complaint');
            $table->text('objective')->nullable()->after('subjective');
            $table->text('assessment')->nullable()->after('objective');
            $table->text('plan')->nullable()->after('assessment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['chief_complaint', 'subjective', 'objective', 'assessment', 'plan']);
        });
    }
};
