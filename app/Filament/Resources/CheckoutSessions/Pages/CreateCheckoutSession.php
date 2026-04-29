<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Models\Appointment;
use App\Services\PracticeContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCheckoutSession extends CreateRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $data['practice_id'] = $practiceId;

        if (empty($data['appointment_id'])) {
            throw ValidationException::withMessages([
                'data.appointment_id' => 'Choose an appointment for this checkout.',
            ]);
        }

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::withoutPracticeScope()
                ->with('appointmentType')
                ->where('practice_id', $practiceId)
                ->find($data['appointment_id']);

            if (! $appointment) {
                throw ValidationException::withMessages([
                    'data.appointment_id' => 'Choose an appointment for the current practice.',
                ]);
            }

            $data['patient_id'] = $appointment->patient_id;
            $data['practitioner_id'] = $appointment->practitioner_id;
            $data['charge_label'] = ($data['charge_label'] ?? null) ?: ($appointment->appointmentType?->name ?: 'Visit');
        }

        return $data;
    }
}
