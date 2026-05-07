<?php

namespace App\Services;

use App\Filament\Resources\AppointmentTypes\AppointmentTypeResource;
use App\Filament\Resources\Practices\PracticeResource;
use App\Filament\Resources\Practitioners\PractitionerResource;
use App\Models\AppointmentType;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerWorkingHour;
use Illuminate\Support\Facades\DB;

class PracticeSetupChecklistService
{
    public function forPractice(Practice $practice): array
    {
        $hasProfile = filled($practice->name)
            && filled($practice->timezone)
            && filled($practice->slug);
        $hasActivePractitioner = $this->hasActivePractitioner($practice);
        $hasActiveAppointmentType = $this->hasActiveAppointmentType($practice);
        $hasCompatibility = $this->hasActiveCompatibility($practice);
        $hasWorkingHours = $this->hasActiveWorkingHours($practice);
        $hasPublicLinks = filled($practice->slug);

        $items = [
            [
                'key' => 'practice_profile',
                'label' => 'Practice profile',
                'complete' => $hasProfile,
                'explanation' => $hasProfile
                    ? 'Practice name, timezone, and public slug are ready.'
                    : 'Add a practice name, timezone, and URL slug before sharing public links.',
                'action_label' => 'Edit practice profile',
                'action_url' => PracticeResource::getUrl('edit', ['record' => $practice]),
            ],
            [
                'key' => 'active_practitioner',
                'label' => 'Active practitioner',
                'complete' => $hasActivePractitioner,
                'explanation' => $hasActivePractitioner
                    ? 'At least one practitioner can be scheduled.'
                    : 'Add an active practitioner before creating a usable schedule.',
                'action_label' => 'Manage practitioners',
                'action_url' => PractitionerResource::getUrl('index'),
            ],
            [
                'key' => 'active_appointment_type',
                'label' => 'Treatment types',
                'complete' => $hasActiveAppointmentType,
                'explanation' => $hasActiveAppointmentType
                    ? 'At least one active treatment type is available.'
                    : 'Create the visit or treatment types patients can request.',
                'action_label' => 'Manage treatment types',
                'action_url' => AppointmentTypeResource::getUrl('index'),
            ],
            [
                'key' => 'practitioner_appointment_type',
                'label' => 'Practitioner treatment compatibility',
                'complete' => $hasCompatibility,
                'explanation' => $hasCompatibility
                    ? 'Patients can see visit types for compatible practitioners.'
                    : 'Attach active practitioners to the treatment types they provide.',
                'action_label' => 'Attach treatment types',
                'action_url' => PractitionerResource::getUrl('index'),
                'warning' => $hasCompatibility
                    ? null
                    : 'Patients will not see visit types until practitioners are attached to treatment types.',
            ],
            [
                'key' => 'working_hours',
                'label' => 'Practitioner working hours',
                'complete' => $hasWorkingHours,
                'explanation' => $hasWorkingHours
                    ? 'At least one active practitioner has working hours for availability checks.'
                    : 'Add working hours so scheduling suggestions can find valid openings.',
                'action_label' => 'Set working hours',
                'action_url' => PractitionerResource::getUrl('index'),
            ],
            [
                'key' => 'public_links',
                'label' => 'Public website links',
                'complete' => $hasPublicLinks,
                'explanation' => $hasPublicLinks
                    ? 'Public new-patient, existing-patient, and appointment request links are ready to copy.'
                    : 'Add a practice slug before sharing website links.',
                'action_label' => 'View website links',
                'action_url' => PracticeResource::getUrl('edit', ['record' => $practice]),
            ],
        ];

        $completeCount = collect($items)->where('complete', true)->count();

        return [
            'items' => $items,
            'complete_count' => $completeCount,
            'total_count' => count($items),
            'is_complete' => $completeCount === count($items),
        ];
    }

    private function hasActivePractitioner(Practice $practice): bool
    {
        return Practitioner::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('is_active', true)
            ->exists();
    }

    private function hasActiveAppointmentType(Practice $practice): bool
    {
        return AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('is_active', true)
            ->exists();
    }

    private function hasActiveCompatibility(Practice $practice): bool
    {
        return DB::table('practitioner_appointment_type')
            ->join('practitioners', 'practitioners.id', '=', 'practitioner_appointment_type.practitioner_id')
            ->join('appointment_types', 'appointment_types.id', '=', 'practitioner_appointment_type.appointment_type_id')
            ->where('practitioner_appointment_type.practice_id', $practice->id)
            ->where('practitioner_appointment_type.is_active', true)
            ->where('practitioners.practice_id', $practice->id)
            ->where('practitioners.is_active', true)
            ->where('appointment_types.practice_id', $practice->id)
            ->where('appointment_types.is_active', true)
            ->exists();
    }

    private function hasActiveWorkingHours(Practice $practice): bool
    {
        return PractitionerWorkingHour::withoutPracticeScope()
            ->join('practitioners', 'practitioners.id', '=', 'practitioner_working_hours.practitioner_id')
            ->where('practitioner_working_hours.practice_id', $practice->id)
            ->where('practitioner_working_hours.is_active', true)
            ->where('practitioners.practice_id', $practice->id)
            ->where('practitioners.is_active', true)
            ->exists();
    }
}
