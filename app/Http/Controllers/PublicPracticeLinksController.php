<?php

namespace App\Http\Controllers;

use App\Mail\PatientPortalMagicLinkMail;
use App\Models\MessageLog;
use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\Practice;
use App\Services\PatientPortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PublicPracticeLinksController extends Controller
{
    public function newPatient(string $practiceSlug): View
    {
        $practice = $this->resolvePractice($practiceSlug);

        return view('public.new-patient-interest', [
            'practice' => $practice,
            'storeRoute' => route('public.practice.new-patient.store', ['practiceSlug' => $practice->slug]),
        ]);
    }

    public function storeNewPatient(Request $request, string $practiceSlug): RedirectResponse
    {
        $practice = $this->resolvePractice($practiceSlug);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'preferred_service' => ['nullable', 'string', 'max:255'],
            'preferred_days_times' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:4000'],
            'contact_acknowledgement' => ['accepted'],
        ]);

        unset($data['contact_acknowledgement']);

        NewPatientInterest::withoutPracticeScope()->create($data + [
            'practice_id' => $practice->id,
            'status' => NewPatientInterest::STATUS_NEW,
        ]);

        return redirect()->route('new-patient.thanks');
    }

    public function existingPatient(string $practiceSlug): View
    {
        return view('public.existing-patient-access', [
            'practice' => $this->resolvePractice($practiceSlug),
            'action' => route('public.practice.existing-patient.store', ['practiceSlug' => $practiceSlug]),
        ]);
    }

    public function sendExistingPatientLink(Request $request, string $practiceSlug, PatientPortalTokenService $tokens): RedirectResponse
    {
        $practice = $this->resolvePractice($practiceSlug);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('is_patient', true)
            ->whereRaw('lower(email) = ?', [strtolower($data['email'])])
            ->first();

        if ($patient && filled($patient->email)) {
            $this->sendPortalLink($patient, $tokens);
        }

        return back()->with('status', $this->genericExistingPatientMessage());
    }

    public function requestAppointment(Request $request, string $practiceSlug): RedirectResponse
    {
        $practice = $this->resolvePractice($practiceSlug);

        if ((int) $request->session()->get('patient_portal_practice_id') === (int) $practice->id
            && $request->session()->has('patient_portal_patient_id')) {
            return redirect()->route('patient.appointment-request.create');
        }

        return redirect()->route('public.practice.existing-patient', ['practiceSlug' => $practiceSlug]);
    }

    private function resolvePractice(string $practiceSlug): Practice
    {
        return Practice::query()
            ->where('slug', $practiceSlug)
            ->where('is_active', true)
            ->where('is_demo', false)
            ->firstOrFail();
    }

    private function sendPortalLink(Patient $patient, PatientPortalTokenService $tokens): void
    {
        [$portalToken, $plainToken] = $tokens->createForExistingPatient($patient);

        $portalUrl = route('patient.magic-link', ['token' => $plainToken]);
        $subject = 'Your secure link for '.$patient->practice->name;
        $body = "Hi {$patient->first_name},\n\nHere is your secure link for {$patient->practice->name}. This link opens a basic patient dashboard and expires on {$portalToken->expires_at->format('M j, Y')}.\n\nIf you did not request this, you can ignore this email.";

        $messageLog = MessageLog::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => null,
            'message_template_id' => null,
            'channel' => 'email',
            'recipient' => $patient->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            Mail::to($patient->email)->send(new PatientPortalMagicLinkMail($messageLog, $portalUrl));
        } catch (\Throwable $exception) {
            $messageLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            return;
        }

        $messageLog->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    private function genericExistingPatientMessage(): string
    {
        return 'If we find a matching patient record, we will send a secure access link to that email address.';
    }
}
