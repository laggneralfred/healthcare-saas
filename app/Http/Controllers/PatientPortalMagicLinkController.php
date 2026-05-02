<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\PatientPortalToken;
use App\Models\Practice;
use App\Services\PatientPortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPortalMagicLinkController extends Controller
{
    public function show(string $token, PatientPortalTokenService $tokens): RedirectResponse
    {
        $portalToken = $tokens->verifyExistingPatientToken($token);
        $redirectRoute = 'patient.dashboard';

        if (! $portalToken) {
            $portalToken = $tokens->verifyExistingPatientFormToken($token);
            $redirectRoute = 'patient.forms.index';
        }

        if (! $portalToken) {
            return redirect()
                ->route('patient.portal.invalid')
                ->with('status', 'This patient portal link is invalid or has expired.');
        }

        session([
            'patient_portal_token_id' => $portalToken->id,
            'patient_portal_practice_id' => $portalToken->practice_id,
            'patient_portal_patient_id' => $portalToken->patient_id,
        ]);

        return redirect()->route($redirectRoute);
    }

    public function dashboard(Request $request): View
    {
        $practiceId = (int) $request->session()->get('patient_portal_practice_id');
        $patientId = (int) $request->session()->get('patient_portal_patient_id');

        return view('patient.dashboard', [
            'practice' => Practice::query()->findOrFail($practiceId),
            'patient' => Patient::withoutPracticeScope()
                ->where('practice_id', $practiceId)
                ->findOrFail($patientId),
            'appointmentRequests' => AppointmentRequest::withoutPracticeScope()
                ->where('practice_id', $practiceId)
                ->where('patient_id', $patientId)
                ->latest('submitted_at')
                ->latest()
                ->limit(5)
                ->get(),
            'formSubmissions' => \App\Models\FormSubmission::withoutPracticeScope()
                ->with('formTemplate')
                ->where('practice_id', $practiceId)
                ->where('patient_id', $patientId)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'patient_portal_token_id',
            'patient_portal_practice_id',
            'patient_portal_patient_id',
        ]);

        return redirect()->route('patient.portal.logged-out');
    }

    public function invalid(): View
    {
        return view('patient.invalid-link');
    }

    public function loggedOut(): View
    {
        return view('patient.logged-out');
    }
}
