<?php

namespace Database\Factories;

use App\Models\LegalAcceptance;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalAcceptance>
 */
class LegalAcceptanceFactory extends Factory
{
    protected $model = LegalAcceptance::class;

    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'user_id' => User::factory(),
            'document_key' => 'terms_of_service',
            'document_version' => config('legal.documents.terms_of_service.version', '2026-05-06'),
            'accepted_at' => now(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Feature test browser',
            'source' => 'register',
        ];
    }
}
