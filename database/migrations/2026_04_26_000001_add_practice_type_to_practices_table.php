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
            $table->string('practice_type')
                ->default('general_wellness')
                ->after('discipline');
        });

        DB::table('practices')->whereIn('discipline', ['acupuncture', 'Acupuncture'])->update([
            'practice_type' => 'tcm_acupuncture',
        ]);
        DB::table('practices')->whereIn('discipline', ['chiropractic', 'Chiropractic'])->update([
            'practice_type' => 'chiropractic',
        ]);
        DB::table('practices')->whereIn('discipline', ['massage', 'massage_therapy', 'Massage Therapy'])->update([
            'practice_type' => 'massage_therapy',
        ]);
        DB::table('practices')->whereIn('discipline', ['physiotherapy', 'physical_therapy', 'Physiotherapy'])->update([
            'practice_type' => 'physiotherapy',
        ]);
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropColumn('practice_type');
        });
    }
};
