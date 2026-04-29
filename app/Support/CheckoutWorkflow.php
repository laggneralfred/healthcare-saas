<?php

namespace App\Support;

use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\States\CheckoutSession\Open;

class CheckoutWorkflow
{
    public static function sessionForEncounter(Encounter $encounter): ?CheckoutSession
    {
        $encounter->loadMissing(['appointment.appointmentType']);

        $patientId = $encounter->patient_id ?: $encounter->appointment?->patient_id;

        if (! $patientId) {
            return null;
        }

        if ($encounter->appointment) {
            return static::appointmentCheckout($encounter);
        }

        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $encounter->practice_id)
            ->where('encounter_id', $encounter->id)
            ->first();

        if ($checkout) {
            return $checkout;
        }

        return $encounter->checkoutSession()->create([
            'practice_id' => $encounter->practice_id,
            'patient_id' => $patientId,
            'practitioner_id' => $encounter->practitioner_id,
            'state' => Open::$name,
            'charge_label' => 'Visit',
        ]);
    }

    private static function appointmentCheckout(Encounter $encounter): CheckoutSession
    {
        $appointment = $encounter->appointment;
        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $encounter->practice_id)
            ->where('appointment_id', $appointment->id)
            ->first();

        if ($checkout) {
            if ($checkout->encounter_id === null) {
                $checkout->forceFill(['encounter_id' => $encounter->id])->save();
            }

            return $checkout;
        }

        return $appointment->checkoutSession()->create([
            'practice_id' => $encounter->practice_id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id ?: $appointment->patient_id,
            'practitioner_id' => $encounter->practitioner_id ?: $appointment->practitioner_id,
            'state' => Open::$name,
            'charge_label' => $appointment->appointmentType?->name ?: 'Visit',
        ]);
    }
}
