<?php

namespace App\Services\Onboarding;

use App\Models\AppointmentType;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerWorkingHour;
use App\Models\ServiceFee;
use App\Models\User;
use App\Support\PracticeType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PracticeStarterDefaultsService
{
    public function seed(Practice $practice, ?User $user = null): array
    {
        $practiceType = PracticeType::fromPractice($practice);
        $user ??= User::where('practice_id', $practice->id)->orderBy('id')->first();

        $created = [
            'practitioner' => false,
            'working_hours' => 0,
            'appointment_types' => 0,
            'service_fees' => 0,
            'compatibilities' => 0,
        ];

        return DB::transaction(function () use ($practice, $user, $practiceType, &$created): array {
            $practitioner = $this->firstOrCreatePractitioner($practice, $user, $practiceType, $created);
            $created['working_hours'] = $this->seedWorkingHours($practice, $practitioner);
            $appointmentTypes = $this->seedAppointmentTypes($practice, $practiceType, $created);
            $created['compatibilities'] = $this->seedCompatibility($practice, $practitioner, $appointmentTypes);

            return [
                'created' => $created,
                'practitioner' => $practitioner->fresh(['user']),
                'appointment_types' => $appointmentTypes->map->fresh(['defaultServiceFee'])->values(),
                'working_hours' => $this->workingHoursSummary($practitioner),
            ];
        });
    }

    public function summary(Practice $practice): array
    {
        $practitioner = Practitioner::withoutPracticeScope()
            ->with('user')
            ->where('practice_id', $practice->id)
            ->orderBy('id')
            ->first();

        $appointmentTypes = AppointmentType::withoutPracticeScope()
            ->with('defaultServiceFee')
            ->where('practice_id', $practice->id)
            ->orderByRaw("case when name = 'Initial Visit' then 0 else 1 end")
            ->orderBy('id')
            ->get();

        return [
            'practice' => $practice,
            'practice_type_label' => PracticeType::label(PracticeType::fromPractice($practice)),
            'practitioner' => $practitioner,
            'working_hours' => $practitioner ? $this->workingHoursSummary($practitioner) : null,
            'appointment_types' => $appointmentTypes,
            'has_treatment_room_model' => false,
        ];
    }

    private function firstOrCreatePractitioner(Practice $practice, ?User $user, string $practiceType, array &$created): Practitioner
    {
        $existing = Practitioner::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $user) {
            throw new RuntimeException('Cannot create starter practitioner without a practice user.');
        }

        $created['practitioner'] = true;

        return Practitioner::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'user_id' => $user?->id,
            'specialty' => $this->specialtyFor($practiceType),
            'clinical_style' => null,
            'is_active' => true,
        ]);
    }

    private function seedWorkingHours(Practice $practice, Practitioner $practitioner): int
    {
        $hasHours = PractitionerWorkingHour::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('practitioner_id', $practitioner->id)
            ->exists();

        if ($hasHours) {
            return 0;
        }

        foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
            PractitionerWorkingHour::withoutPracticeScope()->create([
                'practice_id' => $practice->id,
                'practitioner_id' => $practitioner->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);
        }

        return 5;
    }

    private function seedAppointmentTypes(Practice $practice, string $practiceType, array &$created): Collection
    {
        $existing = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->orderBy('id')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        return collect([
            ['name' => 'Initial Visit', 'duration' => 60, 'price' => $this->pricesFor($practiceType)['initial']],
            ['name' => 'Follow-up Visit', 'duration' => 45, 'price' => $this->pricesFor($practiceType)['follow_up']],
        ])->map(function (array $starter) use ($practice, &$created): AppointmentType {
            $serviceFee = ServiceFee::withoutPracticeScope()->firstOrCreate(
                [
                    'practice_id' => $practice->id,
                    'name' => $starter['name'],
                ],
                [
                    'short_description' => 'Starter fee created during trial setup.',
                    'default_price' => $starter['price'],
                    'is_active' => true,
                ],
            );

            if ($serviceFee->wasRecentlyCreated) {
                $created['service_fees']++;
            }

            $appointmentType = AppointmentType::withoutPracticeScope()->create([
                'practice_id' => $practice->id,
                'name' => $starter['name'],
                'duration_minutes' => $starter['duration'],
                'default_service_fee_id' => $serviceFee->id,
                'is_active' => true,
            ]);

            $created['appointment_types']++;

            return $appointmentType;
        });
    }

    private function seedCompatibility(Practice $practice, Practitioner $practitioner, Collection $appointmentTypes): int
    {
        $created = 0;

        foreach ($appointmentTypes as $appointmentType) {
            if ((int) $appointmentType->practice_id !== (int) $practice->id) {
                continue;
            }

            $exists = DB::table('practitioner_appointment_type')
                ->where('practice_id', $practice->id)
                ->where('practitioner_id', $practitioner->id)
                ->where('appointment_type_id', $appointmentType->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('practitioner_appointment_type')->insert([
                'practice_id' => $practice->id,
                'practitioner_id' => $practitioner->id,
                'appointment_type_id' => $appointmentType->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        return $created;
    }

    private function workingHoursSummary(Practitioner $practitioner): ?array
    {
        $hours = PractitionerWorkingHour::withoutPracticeScope()
            ->where('practice_id', $practitioner->practice_id)
            ->where('practitioner_id', $practitioner->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();

        if ($hours->isEmpty()) {
            return null;
        }

        return [
            'label' => $hours->pluck('day_of_week')->map(
                fn (int $day): string => PractitionerWorkingHour::DAYS[$day] ?? 'Unknown',
            )->implode(', '),
            'time' => $hours->first()->start_time.'-'.$hours->first()->end_time,
            'count' => $hours->count(),
        ];
    }

    private function specialtyFor(string $practiceType): string
    {
        return match (PracticeType::normalize($practiceType)) {
            PracticeType::TCM_ACUPUNCTURE => 'Acupuncture',
            PracticeType::FIVE_ELEMENT_ACUPUNCTURE => 'Five Element Acupuncture',
            PracticeType::CHIROPRACTIC => 'Chiropractic',
            PracticeType::MASSAGE_THERAPY => 'Massage Therapy',
            PracticeType::PHYSIOTHERAPY => 'Physiotherapy',
            default => 'Wellness',
        };
    }

    private function pricesFor(string $practiceType): array
    {
        return match (PracticeType::normalize($practiceType)) {
            PracticeType::TCM_ACUPUNCTURE, PracticeType::FIVE_ELEMENT_ACUPUNCTURE => [
                'initial' => 125,
                'follow_up' => 90,
            ],
            PracticeType::MASSAGE_THERAPY => [
                'initial' => 100,
                'follow_up' => 85,
            ],
            PracticeType::CHIROPRACTIC => [
                'initial' => 110,
                'follow_up' => 75,
            ],
            PracticeType::PHYSIOTHERAPY => [
                'initial' => 130,
                'follow_up' => 95,
            ],
            default => [
                'initial' => 100,
                'follow_up' => 75,
            ],
        };
    }
}
