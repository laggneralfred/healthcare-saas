<?php

namespace App\Support;

use App\Models\Practice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PracticeAccessRoles
{
    public static function ensureRoles(): void
    {
        foreach (self::roleNames() as $role) {
            Role::findOrCreate($role, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function backfillExistingUsers(): void
    {
        self::ensureRoles();

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', self::roleNames())
            ->pluck('id', 'name');

        DB::table('users')
            ->orderBy('id')
            ->select(['id', 'practice_id'])
            ->chunkById(100, function ($users) use ($roleIds): void {
                foreach ($users as $user) {
                    if (self::userHasPracticeAccessRole((int) $user->id)) {
                        continue;
                    }

                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $roleIds[self::roleForUser((int) $user->id, $user->practice_id ? (int) $user->practice_id : null)],
                        'model_type' => User::class,
                        'model_id' => $user->id,
                    ]);
                }
            });

        DB::table('practices')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(100, function ($practices): void {
                foreach ($practices as $practice) {
                    self::ensurePracticeHasOwner((int) $practice->id);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function assignOwner(User $user): void
    {
        self::ensureRoles();

        $user->assignRole(User::ROLE_OWNER);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function ensurePracticeHasOwner(Practice|int $practice): void
    {
        self::ensureRoles();

        $practiceId = $practice instanceof Practice ? (int) $practice->id : $practice;

        if ($practiceId < 1 || self::practiceHasOwner($practiceId)) {
            return;
        }

        $ownerCandidateId = self::ownerCandidateId($practiceId);

        if (! $ownerCandidateId) {
            return;
        }

        $user = User::withoutGlobalScopes()->find($ownerCandidateId);

        if ($user) {
            self::assignOwner($user);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function roleNames(): array
    {
        return [
            User::ROLE_OWNER,
            User::ROLE_ADMINISTRATOR,
            User::ROLE_PRACTITIONER,
        ];
    }

    private static function roleForUser(int $userId, ?int $practiceId): string
    {
        if ($practiceId === null) {
            return User::ROLE_OWNER;
        }

        $firstUserId = DB::table('users')
            ->where('practice_id', $practiceId)
            ->orderBy('id')
            ->value('id');

        if ((int) $firstUserId === $userId) {
            return User::ROLE_OWNER;
        }

        $linkedToPractitioner = DB::table('practitioners')
            ->where('practice_id', $practiceId)
            ->where('user_id', $userId)
            ->exists();

        return $linkedToPractitioner
            ? User::ROLE_PRACTITIONER
            : User::ROLE_ADMINISTRATOR;
    }

    private static function userHasPracticeAccessRole(int $userId): bool
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $userId)
            ->where('roles.guard_name', 'web')
            ->whereIn('roles.name', self::roleNames())
            ->exists();
    }

    private static function practiceHasOwner(int $practiceId): bool
    {
        return DB::table('users')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.practice_id', $practiceId)
            ->where('roles.guard_name', 'web')
            ->where('roles.name', User::ROLE_OWNER)
            ->exists();
    }

    private static function ownerCandidateId(int $practiceId): ?int
    {
        return self::administratorCandidateId($practiceId)
            ?? self::firstUserId($practiceId);
    }

    private static function administratorCandidateId(int $practiceId): ?int
    {
        $userId = DB::table('users')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.practice_id', $practiceId)
            ->where('roles.guard_name', 'web')
            ->where('roles.name', User::ROLE_ADMINISTRATOR)
            ->orderBy('users.id')
            ->value('users.id');

        return $userId ? (int) $userId : null;
    }

    private static function firstUserId(int $practiceId): ?int
    {
        $userId = DB::table('users')
            ->where('practice_id', $practiceId)
            ->orderBy('id')
            ->value('id');

        return $userId ? (int) $userId : null;
    }
}
