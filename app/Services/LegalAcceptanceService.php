<?php

namespace App\Services;

use App\Models\LegalAcceptance;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Http\Request;

class LegalAcceptanceService
{
    public const HIPAA_BAA_ACKNOWLEDGEMENT = 'hipaa_baa_acknowledgement';
    public const AI_DISCLAIMER_ACKNOWLEDGEMENT = 'ai_disclaimer_acknowledgement';

    public function currentVersion(string $documentKey): ?string
    {
        return config("legal.documents.{$documentKey}.version");
    }

    public function hasCurrentAcceptance(Practice|int $practice, string $documentKey): bool
    {
        $practiceId = $practice instanceof Practice ? $practice->id : $practice;
        $version = $this->currentVersion($documentKey);

        if (! $version) {
            return false;
        }

        return LegalAcceptance::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('document_key', $documentKey)
            ->where('document_version', $version)
            ->exists();
    }

    public function hasAcceptedCurrentVersion(Practice|int $practice, string $documentKey): bool
    {
        return $this->hasCurrentAcceptance($practice, $documentKey);
    }

    public function latestCurrentAcceptance(Practice|int $practice, string $documentKey): ?LegalAcceptance
    {
        $practiceId = $practice instanceof Practice ? $practice->id : $practice;
        $version = $this->currentVersion($documentKey);

        if (! $version) {
            return null;
        }

        return LegalAcceptance::withoutPracticeScope()
            ->with('user')
            ->where('practice_id', $practiceId)
            ->where('document_key', $documentKey)
            ->where('document_version', $version)
            ->latest('accepted_at')
            ->latest('id')
            ->first();
    }

    public function acceptCurrent(Practice $practice, User $user, string $documentKey, Request $request, string $source): LegalAcceptance
    {
        $version = $this->currentVersion($documentKey);

        return LegalAcceptance::withoutPracticeScope()->firstOrCreate(
            [
                'practice_id' => $practice->id,
                'document_key' => $documentKey,
                'document_version' => $version,
            ],
            [
                'user_id' => $user->id,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'source' => $source,
            ],
        );
    }

    public function recordAcceptance(Practice $practice, User $user, string $documentKey, Request $request, string $source): LegalAcceptance
    {
        return $this->acceptCurrent($practice, $user, $documentKey, $request, $source);
    }
}
