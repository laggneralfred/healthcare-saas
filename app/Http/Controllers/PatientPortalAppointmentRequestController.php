<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\Practice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientPortalAppointmentRequestController extends Controller
{
    public function create(Request $request): View
    {
        [$practice, $patient] = $this->portalContext($request);

        return view('patient.appointment-request', [
            'practice' => $practice,
            'patient' => $patient,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$practice, $patient] = $this->portalContext($request);

        $validated = $request->validate([
            'requested_service' => ['nullable', 'string', 'max:255'],
            'preferred_days_times' => ['required', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'patient_communication_id' => null,
            'token_hash' => $this->uniqueUnusedTokenHash(),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => trim((string) ($validated['requested_service'] ?? '')) ?: null,
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
