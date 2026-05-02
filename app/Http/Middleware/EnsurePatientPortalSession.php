<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use App\Models\PatientPortalToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientPortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenId = $request->session()->get('patient_portal_token_id');
        $practiceId = $request->session()->get('patient_portal_practice_id');
        $patientId = $request->session()->get('patient_portal_patient_id');

        if (! $tokenId || ! $practiceId || ! $patientId) {
            return redirect()->route('patient.portal.invalid');
        }

        $portalToken = PatientPortalToken::withoutPracticeScope()
            ->where('id', $tokenId)
            ->where('practice_id', $practiceId)
            ->where('patient_id', $patientId)
            ->whereIn('purpose', [
                PatientPortalToken::PURPOSE_EXISTING_PATIENT_PORTAL,
                PatientPortalToken::PURPOSE_EXISTING_PATIENT_FORM,
            ])
            ->first();

        $patientExists = Patient::withoutPracticeScope()
            ->where('id', $patientId)
            ->where('practice_id', $practiceId)
            ->exists();

        if (! $portalToken || $portalToken->isExpired() || ! $patientExists) {
            $request->session()->forget([
                'patient_portal_token_id',
                'patient_portal_practice_id',
                'patient_portal_patient_id',
            ]);

            return redirect()->route('patient.portal.invalid');
        }

        return $next($request);
    }
}
