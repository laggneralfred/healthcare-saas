<?php

use App\Database\SafeMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends SafeMigration
{
    public function safeUp(): void
    {
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->default(60)->after('name');
        });
    }

    public function safeDown(): void
    {
        Schema::table('appointment_types', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};
