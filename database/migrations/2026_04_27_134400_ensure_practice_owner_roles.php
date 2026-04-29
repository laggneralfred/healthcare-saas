<?php

use App\Models\Practice;
use App\Support\PracticeAccessRoles;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PracticeAccessRoles::ensureRoles();

        Practice::query()
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($practices): void {
                foreach ($practices as $practice) {
                    PracticeAccessRoles::ensurePracticeHasOwner($practice);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
