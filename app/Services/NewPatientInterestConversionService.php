<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NewPatientInterestConversionService
{
    public function convert(NewPatientInterest $interest, ?User $user = null): Patient
    {
        if ($interest->converted_patient_id || $interest->status === NewPatientInterest::STATUS_CONVERTED) {
            throw ValidationException::withMessages([
                'interest' => 'This new patient interest has already been converted.',
            ]);
        }

        if (in_array($interest->status, [NewPatientInterest::STATUS_DECLINED, NewPatientInterest::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages([
                'interest' => 'Closed or declined interests cannot be converted.',
            ]);
        }

        if (filled($interest->email) && Patient::withoutPracticeScope()
            ->where('practice_id', $interest->practice_id)
            ->whereRaw('lower(email) = ?', [strtolower($interest->email)])
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A patient with this email already exists in this practice. Please review before converting.',
            ]);
        }

        return DB::transaction(function () use ($interest, $user): Patient {
            $patient = Patient::withoutPracticeScope()->create($this->patientDataFor($interest));

            FormSubmission::withoutPracticeScope()
                ->where('practice_id', $interest->practice_id)
                ->where('new_patient_interest_id', $interest->id)
                ->update(['patient_id' => $patient->id]);

            $interest->update([
                'status' => NewPatientInterest::STATUS_CONVERTED,
                'converted_patient_id' => $patient->id,
                'responded_at' => now(),
                'responded_by_user_id' => $user?->id,
            ]);

            return $patient;
        });
    }

    private function patientDataFor(NewPatientInterest $interest): array
    {
        $submitted = $this->submittedDataFor($interest);

        return array_filter([
            'practice_id' => $interest->practice_id,
            'first_name' => $interest->first_name,
            'last_name' => $interest->last_name,
            'name' => trim($interest->first_name.' '.$interest->last_name),
            'email' => $interest->email,
            'phone' => $interest->phone,
            'dob' => $this->firstFilled($submitted, ['date_of_birth', 'dob']),
            'gender' => $this->firstFilled($submitted, ['gender']),
            'address_line_1' => $this->firstFilled($submitted, ['address_line_1', 'address', 'address1']),
            'address_line_2' => $this->firstFilled($submitted, ['address_line_2', 'address2']),
            'city' => $this->firstFilled($submitted, ['city']),
            'state' => $this->firstFilled($submitted, ['state']),
            'postal_code' => $this->firstFilled($submitted, ['postal_code', 'zip']),
            'preferred_language' => $this->firstFilled($submitted, ['preferred_language']),
            'emergency_contact_name' => $this->firstFilled($submitted, ['emergency_contact_name']),
            'occupation' => $this->firstFilled($submitted, ['occupation']),
            'is_patient' => true,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function submittedDataFor(NewPatientInterest $interest): array
    {
        $submissions = $interest->formSubmissions()
            ->whereNotNull('submitted_data_json')
            ->whereIn('status', [
                FormSubmission::STATUS_SUBMITTED,
                FormSubmission::STATUS_REVIEWED,
                FormSubmission::STATUS_ACCEPTED,
            ])
            ->oldest()
            ->get();

        $data = [];

        foreach ($submissions as $submission) {
            $data = array_merge($data, $submission->submitted_data_json ?? []);
        }

        return $data;
    }

    private function firstFilled(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }
}
