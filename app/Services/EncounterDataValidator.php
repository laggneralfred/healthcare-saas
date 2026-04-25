<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Practitioner;
use Illuminate\Validation\ValidationException;

class EncounterDataValidator
{
    /**
     * Normalize optional encounter links and verify every selected record belongs
     * to the active practice context before the form data reaches persistence.
     */
    public static function forCurrentPractice(array $data): array
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            throw ValidationException::withMessages([
                'practice_id' => 'Select a practice before saving an encounter.',
            ]);
        }

        $data['practice_id'] = $practiceId;
        $data['appointment_id'] = filled($data['appointment_id'] ?? null)
            ? (int) $data['appointment_id']
            : null;

        $patient = Patient::query()
            ->whereKey($data['patient_id'] ?? null)
            ->where('practice_id', $practiceId)
            ->first();

        if (! $patient) {
            throw ValidationException::withMessages([
                'patient_id' => 'Select a patient in the current practice.',
            ]);
        }

        $practitioner = Practitioner::query()
            ->whereKey($data['practitioner_id'] ?? null)
            ->where('practice_id', $practiceId)
            ->first();

        if (! $practitioner) {
            throw ValidationException::withMessages([
                'practitioner_id' => 'Select a practitioner in the current practice.',
            ]);
        }

        if (! $data['appointment_id']) {
            return $data;
        }

        $appointment = Appointment::query()
            ->whereKey($data['appointment_id'])
            ->where('practice_id', $practiceId)
            ->first();

        if (! $appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Select an appointment in the current practice.',
            ]);
        }

        if ((int) $appointment->patient_id !== (int) $patient->id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected appointment does not belong to this patient.',
            ]);
        }

        if ((int) $appointment->practitioner_id !== (int) $practitioner->id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected appointment does not belong to this practitioner.',
            ]);
        }

        return $data;
    }
}
