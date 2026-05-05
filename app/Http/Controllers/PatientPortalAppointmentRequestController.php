<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Services\Scheduling\AppointmentRequestOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientPortalAppointmentRequestController extends Controller
{
    public function create(Request $request, AppointmentRequestOptionsService $options): View
    {
        [$practice, $patient] = $this->portalContext($request);
        $appointmentTypes = $options->appointmentTypesForPortal($practice, $patient);
        $selectedAppointmentTypeId = (int) ($request->old('appointment_type_id') ?: $request->query('appointment_type_id'));
        $selectedAppointmentType = $appointmentTypes->firstWhere('id', $selectedAppointmentTypeId);
        $practitioners = $selectedAppointmentType
            ? $options->practitionersForAppointmentType($selectedAppointmentType)
            : collect();
        $suggestedPractitioner = $selectedAppointmentType
            ? $options->suggestedPractitioner($patient, $selectedAppointmentType)
            : null;

        return view('patient.appointment-request', [
            'practice' => $practice,
            'patient' => $patient,
            'appointmentTypes' => $appointmentTypes,
            'practitioners' => $practitioners,
            'selectedAppointmentType' => $selectedAppointmentType,
            'suggestedPractitioner' => $suggestedPractitioner,
        ]);
    }

    public function store(Request $request, AppointmentRequestOptionsService $options): RedirectResponse
    {
        [$practice, $patient] = $this->portalContext($request);

        $validated = $request->validate([
            'appointment_type_id' => [
                'required',
                'integer',
                Rule::exists('appointment_types', 'id')->where(fn ($query) => $query
                    ->where('practice_id', $practice->id)
                    ->where('is_active', true)),
            ],
            'practitioner_id' => [
                'nullable',
                'integer',
                Rule::exists('practitioners', 'id')->where(fn ($query) => $query
                    ->where('practice_id', $practice->id)
                    ->where('is_active', true)),
            ],
            'preferred_days_times' => ['required', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $appointmentType = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('is_active', true)
            ->findOrFail($validated['appointment_type_id']);
        $validPractitionerIds = $options->practitionersForAppointmentType($appointmentType)
            ->pluck('id')
            ->all();

        if ($validPractitionerIds === []) {
            throw ValidationException::withMessages([
                'appointment_type_id' => 'Choose a treatment that is currently available for this clinic.',
            ]);
        }

        $practitionerId = $validated['practitioner_id'] ?? null;

        if ($practitionerId !== null && ! in_array((int) $practitionerId, $validPractitionerIds, true)) {
            throw ValidationException::withMessages([
                'practitioner_id' => 'Choose a practitioner who offers this visit type.',
            ]);
        }

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'patient_communication_id' => null,
            'token_hash' => $this->uniqueUnusedTokenHash(),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => $appointmentType->name,
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitionerId ? (int) $practitionerId : null,
            'preferred_times' => trim($validated['preferred_days_times']),
            'note' => trim((string) ($validated['message'] ?? '')) ?: null,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('patient.dashboard')
            ->with('appointment_request_status', 'Your request was sent. The clinic will contact you to confirm an appointment.');
    }

    private function portalContext(Request $request): array
    {
        $practiceId = (int) $request->session()->get('patient_portal_practice_id');
        $patientId = (int) $request->session()->get('patient_portal_patient_id');

        $practice = Practice::query()->findOrFail($practiceId);
        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->findOrFail($patientId);

        return [$practice, $patient];
    }

    private function uniqueUnusedTokenHash(): string
    {
        do {
            $hash = hash('sha256', Str::random(64));
        } while (AppointmentRequest::withoutPracticeScope()->where('token_hash', $hash)->exists());

        return $hash;
    }
}
