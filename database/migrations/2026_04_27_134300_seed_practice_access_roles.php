<?php

use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        PracticeAccessRoles::backfillExistingUsers();
    }

    public function down(): void
    {
        DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('role_id', DB::table('roles')
                ->where('guard_name', 'web')
                ->whereIn('name', PracticeAccessRoles::roleNames())
                ->select('id'))
            ->delete();

        DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', PracticeAccessRoles::roleNames())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
