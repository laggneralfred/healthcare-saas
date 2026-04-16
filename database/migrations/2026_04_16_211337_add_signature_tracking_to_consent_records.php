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
        Schema::table('consent_records', function (Blueprint $table) {
            $table->ipAddress('signed_at_ip')->nullable()->after('signed_on');
            $table->text('signed_at_user_agent')->nullable()->after('signed_at_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_records', function (Blueprint $table) {
            $table->dropColumn(['signed_at_ip', 'signed_at_user_agent']);
        });
    }
};
