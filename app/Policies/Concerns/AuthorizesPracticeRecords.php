<?php

namespace App\Policies\Concerns;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesPracticeRecords
{
    protected function samePractice(User $user, Model $record): bool
    {
        return $user->isPracticeSuperAdmin()
            || (int) $user->practice_id === (int) $record->getAttribute('practice_id');
    }

    protected function canManagePracticeRecord(User $user, Model $record): bool
    {
        return $this->samePractice($user, $record) && $user->canManageOperations();
    }

    protected function practitionerFor(User $user): ?Practitioner
    {
        if (! $user->isPractitioner()) {
            return null;
        }

        return $user->relationLoaded('practitioner')
            ? $user->practitioner
            : $user->practitioner()->first();
    }

    protected function assignedAppointment(User $user, Appointment $appointment): bool
    {
        if (! $this->samePractice($user, $appointment)) {
            return false;
        }

        $practitioner = $this->practitionerFor($user);

        return $practitioner !== null
            && (int) $appointment->practitioner_id === (int) $practitioner->id;
    }

    protected function assignedEncounter(User $user, Encounter $encounter): bool
    {
        if (! $this->samePractice($user, $encounter)) {
            return false;
        }

        $practitioner = $this->practitionerFor($user);

        if (! $practitioner) {
            return false;
        }

        if ((int) $encounter->practitioner_id === (int) $practitioner->id) {
            return true;
        }

        $appointment = $encounter->relationLoaded('appointment')
            ? $encounter->appointment
            : $encounter->appointment()->first();

        return $appointment instanceof Appointment
            && (int) $appointment->practitioner_id === (int) $practitioner->id;
    }

    protected function assignedMedicalHistory(User $user, MedicalHistory $medicalHistory): bool
    {
        if (! $this->samePractice($user, $medicalHistory)) {
            return false;
        }

        $practitioner = $this->practitionerFor($user);

        if (! $practitioner) {
            return false;
        }

        if ($medicalHistory->practitioner_id !== null) {
            return (int) $medicalHistory->practitioner_id === (int) $practitioner->id;
        }

        $appointment = $medicalHistory->relationLoaded('appointment')
            ? $medicalHistory->appointment
            : $medicalHistory->appointment()->first();

        return $appointment instanceof Appointment
            && (int) $appointment->practitioner_id === (int) $practitioner->id;
    }

    protected function assignedPatient(User $user, Patient $patient): bool
    {
        if (! $this->samePractice($user, $patient)) {
            return false;
        }

        $practitioner = $this->practitionerFor($user);

        if (! $practitioner) {
            return false;
        }

        return $patient->appointments()
            ->where('practitioner_id', $practitioner->id)
            ->exists()
            || $patient->encounters()
                ->where('practitioner_id', $practitioner->id)
                ->exists();
    }
}
