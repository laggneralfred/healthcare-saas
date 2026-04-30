<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;

class PatientMessageDraftService
{
    public const TYPE_INVITE_BACK = 'invite_back';

    public function inviteBack(
        Patient $patient,
        Practice|string|null $practice = null,
        Practitioner|string|null $practitioner = null,
        array $context = []
    ): array {
        $languageCode = Patient::normalizePreferredLanguage($patient->preferred_language);
        $languageLabel = Patient::LANGUAGE_OPTIONS[$languageCode] ?? Patient::LANGUAGE_OPTIONS[Patient::LANGUAGE_OTHER];
        $firstName = $this->patientFirstName($patient);
        $senderName = $context['sender_name'] ?? $this->senderName($practice, $practitioner);

        $englishBody = $this->englishInviteBackBody($firstName, $senderName);
        $localizedBody = match ($languageCode) {
            Patient::LANGUAGE_SPANISH => $this->spanishInviteBackBody($firstName, $senderName),
            default => $englishBody,
        };

        $isLocalized = $languageCode === Patient::LANGUAGE_SPANISH;
        $fallbackUsed = ! in_array($languageCode, [Patient::LANGUAGE_ENGLISH, Patient::LANGUAGE_SPANISH], true);

        return [
            'type' => self::TYPE_INVITE_BACK,
            'language_code' => $languageCode,
            'language_label' => $languageLabel,
            'subject' => 'Checking in',
            'body' => $localizedBody,
            'english_body' => $englishBody,
            'localized_body' => $localizedBody,
            'is_localized' => $isLocalized,
            'fallback_used' => $fallbackUsed,
        ];
    }

    private function patientFirstName(Patient $patient): string
    {
        $name = trim((string) ($patient->first_name ?: $patient->preferred_name));

        if ($name !== '') {
            return $name;
        }

        $fullName = trim((string) $patient->name);

        return $fullName !== ''
            ? strtok($fullName, ' ')
            : 'there';
    }

    private function senderName(Practice|string|null $practice, Practitioner|string|null $practitioner): string
    {
        if (is_string($practitioner) && trim($practitioner) !== '') {
            return trim($practitioner);
        }

        if ($practitioner instanceof Practitioner) {
            $name = trim((string) $practitioner->user?->name);

            if ($name !== '') {
                return $name;
            }
        }

        if (is_string($practice) && trim($practice) !== '') {
            return trim($practice);
        }

        if ($practice instanceof Practice && trim((string) $practice->name) !== '') {
            return trim($practice->name);
        }

        return 'your care team';
    }

    private function englishInviteBackBody(string $firstName, string $senderName): string
    {
        return <<<BODY
Hi {$firstName},

I hope you're doing well. I wanted to gently check in and see how you've been feeling since your last visit.

If you'd like to continue care or schedule a follow-up, you're welcome to book another appointment when the timing feels right.

Warmly,
{$senderName}
BODY;
    }

    private function spanishInviteBackBody(string $firstName, string $senderName): string
    {
        return <<<BODY
Hola {$firstName},

Espero que estes bien. Queria saludarte y saber como te has sentido desde tu ultima visita.

Si deseas continuar con tu cuidado o programar una visita de seguimiento, con gusto puedes hacer otra cita cuando sea un buen momento para ti.

Con aprecio,
{$senderName}
BODY;
    }
}
