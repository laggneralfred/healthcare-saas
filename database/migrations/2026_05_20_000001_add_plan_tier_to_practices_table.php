<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->string('plan_tier')
                ->default('starter')
                ->after('practice_type');
        });

        DB::table('practices')
            ->whereNull('plan_tier')
            ->update(['plan_tier' => 'starter']);
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropColumn('plan_tier');
        });
    }
};
