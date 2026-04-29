<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Practice;
use App\Models\Practitioner;

class ClinicalStyle
{
    public static function fromPractitioner(?Practitioner $practitioner, ?Practice $practice = null): string
    {
        if ($practitioner?->clinical_style) {
            return PracticeType::normalize($practitioner->clinical_style);
        }

        return PracticeType::fromPractice($practice ?? $practitioner?->practice);
    }

    public static function fromEncounter(?Encounter $encounter): string
    {
        if (! $encounter) {
            return PracticeType::GENERAL_WELLNESS;
        }

        return self::fromPractitioner($encounter->practitioner, $encounter->practice);
    }

    public static function fromAppointment(?Appointment $appointment): string
    {
        if (! $appointment) {
            return PracticeType::GENERAL_WELLNESS;
        }

        return self::fromPractitioner($appointment->practitioner, $appointment->practice);
    }

    public static function fromMedicalHistory(?MedicalHistory $medicalHistory): string
    {
        if (! $medicalHistory) {
            return PracticeType::GENERAL_WELLNESS;
        }

        return self::fromPractitioner($medicalHistory->practitioner, $medicalHistory->practice);
    }
}
