<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasAccessToken
{
    public static function bootHasAccessToken(): void
    {
        static::creating(function ($model) {
            if (empty($model->access_token)) {
                $model->access_token = static::generateUniqueToken();
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('access_token', $token)->exists());

        return $token;
    }

    public static function findByToken(string $token): ?static
    {
        return static::where('access_token', $token)->first();
    }

    public function getPublicUrl(): string
    {
        $routeName = match (static::class) {
            \App\Models\IntakeSubmission::class => 'intake.show',
            \App\Models\ConsentRecord::class    => 'consent.show',
            default                             => throw new \LogicException('No public route for ' . static::class),
        };

        return route($routeName, $this->access_token);
    }
}
