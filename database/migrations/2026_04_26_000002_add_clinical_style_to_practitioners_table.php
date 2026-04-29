<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table) {
            $table->string('clinical_style')->nullable()->after('specialty');
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table) {
            $table->dropColumn('clinical_style');
        });
    }
};
