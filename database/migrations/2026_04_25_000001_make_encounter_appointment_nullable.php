<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE encounters ALTER COLUMN appointment_id DROP NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE encounters MODIFY appointment_id BIGINT UNSIGNED NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE encounters ALTER COLUMN appointment_id SET NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE encounters MODIFY appointment_id BIGINT UNSIGNED NOT NULL'),
            default => null,
        };
    }
};
