<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'practice_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_PRACTITIONER = 'practitioner';

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function practitioner(): HasOne
    {
        return $this->hasOne(Practitioner::class);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(self::ROLE_OWNER);
    }

    public function isAdministrator(): bool
    {
        // Temporary compatibility fallback for pre-role users. New users should
        // receive an explicit owner, administrator, or practitioner role.
        return $this->hasRole(self::ROLE_ADMINISTRATOR) || $this->hasNoPracticeAccessRole();
    }

    public function isPractitioner(): bool
    {
        return $this->hasRole(self::ROLE_PRACTITIONER);
    }

    public function canManageOperations(): bool
    {
        return $this->isOwner() || $this->isAdministrator();
    }

    public function isPracticeSuperAdmin(): bool
    {
        return $this->practice_id === null;
    }

    private function hasNoPracticeAccessRole(): bool
    {
        return ! $this->hasAnyRole([
            self::ROLE_OWNER,
            self::ROLE_ADMINISTRATOR,
            self::ROLE_PRACTITIONER,
        ]);
    }

    public function isDemo(): bool
    {
        return $this->relationLoaded('practice')
            ? $this->practice?->is_demo ?? false
            : $this->practice()->value('is_demo') ?? false;
    }
}
