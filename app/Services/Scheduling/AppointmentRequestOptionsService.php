<?php

namespace App\Services\Scheduling;

use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Database\Eloquent\Collection;

class AppointmentRequestOptionsService
{
    public function appointmentTypesForPortal(Practice $practice, Patient $patient): Collection
    {
        return AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('is_active', true)
            ->whereHas('practitioners', function ($query) use ($practice): void {
                $query->where('practitioners.practice_id', $practice->id)
                    ->where('practitioners.is_active', true)
                    ->where('practitioner_appointment_type.practice_id', $practice->id)
                    ->where('practitioner_appointment_type.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function practitionersForAppointmentType(AppointmentType $appointmentType): Collection
    {
        return Practitioner::withoutPracticeScope()
            ->with('user')
            ->where('practice_id', $appointmentType->practice_id)
            ->where('is_active', true)
            ->whereHas('appointmentTypes', function ($query) use ($appointmentType): void {
                $query->where('appointment_types.id', $appointmentType->id)
                    ->where('appointment_types.practice_id', $appointmentType->practice_id)
                    ->where('appointment_types.is_active', true)
                    ->where('practitioner_appointment_type.practice_id', $appointmentType->practice_id)
                    ->where('practitioner_appointment_type.is_active', true);
            })
            ->get()
            ->sortBy(fn (Practitioner $practitioner): string => $practitioner->user?->name ?? "Practitioner #{$practitioner->id}")
            ->values();
    }

    public function appointmentTypesForPractitioner(Practitioner $practitioner): Collection
    {
        return AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practitioner->practice_id)
            ->where('is_active', true)
            ->whereHas('practitioners', function ($query) use ($practitioner): void {
                $query->where('practitioners.id', $practitioner->id)
                    ->where('practitioners.practice_id', $practitioner->practice_id)
                    ->where('practitioners.is_active', true)
                    ->where('practitioner_appointment_type.practice_id', $practitioner->practice_id)
                    ->where('practitioner_appointment_type.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function suggestedPractitioner(Patient $patient, AppointmentType $appointmentType): ?Practitioner
    {
        if ((int) $patient->practice_id !== (int) $appointmentType->practice_id) {
            return null;
        }

        $validPractitionerIds = $this->practitionersForAppointmentType($appointmentType)
            ->pluck('id')
            ->all();

        if ($validPractitionerIds === []) {
            return null;
        }

        $appointment = $patient->appointments()
            ->withoutGlobalScopes()
            ->where('practice_id', $patient->practice_id)
            ->where('appointment_type_id', $appointmentType->id)
            ->whereIn('practitioner_id', $validPractitionerIds)
            ->whereNotNull('start_datetime')
            ->latest('start_datetime')
            ->first();

        return $appointment?->practitioner;
    }
}
