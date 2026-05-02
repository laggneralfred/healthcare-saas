<?php

namespace App\Services;

use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\PatientPortalToken;
use App\Models\User;
use Illuminate\Support\Str;

class PatientPortalTokenService
{
    public function createForExistingPatient(Patient $patient, ?User $creator = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainToken = null;
        $hash = $this->uniqueHash($plainToken);

        $portalToken = PatientPortalToken::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'purpose' => PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL,
            'token_hash' => $hash,
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'created_by_user_id' => $creator?->id,
        ]);

        return [$portalToken, $plainToken];
    }

    public function createForNewPatientInterest(NewPatientInterest $interest, ?User $creator = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainToken = null;
        $hash = $this->uniqueHash($plainToken);

        $portalToken = PatientPortalToken::withoutPracticeScope()->create([
            'practice_id' => $interest->practice_id,
            'patient_id' => null,
            'new_patient_interest_id' => $interest->id,
            'purpose' => PatientPortalToken::PURPOSE_NEW_PATIENT_FORM,
            'token_hash' => $hash,
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'created_by_user_id' => $creator?->id,
        ]);

        return [$portalToken, $plainToken];
    }

    public function createForExistingPatientForm(Patient $patient, ?User $creator = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainToken = null;
        $hash = $this->uniqueHash($plainToken);

        $portalToken = PatientPortalToken::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'purpose' => PatientPortalToken::PURPOSE_EXISTING_PATIENT_FORM,
            'token_hash' => $hash,
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'created_by_user_id' => $creator?->id,
        ]);

        return [$portalToken, $plainToken];
    }

    public function verifyExistingPatientToken(string $plainToken): ?PatientPortalToken
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->with(['practice', 'patient'])
            ->where('purpose', PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL)
            ->where('token_hash', $this->hash($plainToken))
            ->first();

        if (! $portalToken || $portalToken->isExpired() || ! $portalToken->patient || ! $portalToken->practice) {
            return null;
        }

        if ((int) $portalToken->patient->practice_id !== (int) $portalToken->practice_id) {
            return null;
        }

        $this->markUsed($portalToken);

        return $portalToken;
    }

    public function verifyNewPatientFormToken(string $plainToken): ?PatientPortalToken
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->with(['practice', 'newPatientInterest'])
            ->where('purpose', PatientPortalToken::PURPOSE_NEW_PATIENT_FORM)
            ->where('token_hash', $this->hash($plainToken))
            ->first();

        if (! $portalToken || $portalToken->isExpired() || ! $portalToken->newPatientInterest || ! $portalToken->practice) {
            return null;
        }

        if ((int) $portalToken->newPatientInterest->practice_id !== (int) $portalToken->practice_id) {
            return null;
        }

        $this->markUsed($portalToken);

        return $portalToken;
    }

    public function verifyExistingPatientFormToken(string $plainToken): ?PatientPortalToken
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->with(['practice', 'patient'])
            ->where('purpose', PatientPortalToken::PURPOSE_EXISTING_PATIENT_FORM)
            ->where('token_hash', $this->hash($plainToken))
            ->first();

        if (! $portalToken || $portalToken->isExpired() || ! $portalToken->patient || ! $portalToken->practice) {
            return null;
        }

        if ((int) $portalToken->patient->practice_id !== (int) $portalToken->practice_id) {
            return null;
        }

        $this->markUsed($portalToken);

        return $portalToken;
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function uniqueHash(?string &$plainToken): string
    {
        do {
            $plainToken = Str::random(64);
            $hash = $this->hash($plainToken);
        } while (PatientPortalToken::withoutPracticeScope()->where('token_hash', $hash)->exists());

        return $hash;
    }

    private function markUsed(PatientPortalToken $portalToken): void
    {
        $portalToken->forceFill([
            'used_at' => $portalToken->used_at ?? now(),
            'last_used_at' => now(),
        ])->save();
    }
}
