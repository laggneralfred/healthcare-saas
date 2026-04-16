<?php

namespace App\Livewire\Public;

use App\Models\ConsentRecord;
use App\Services\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.public', ['title' => 'Patient Consent Form'])]
class ConsentForm extends Component
{
    public ConsentRecord $record;

    #[Validate('required|string|max:255')]
    public string $consent_given_by = '';

    #[Validate('nullable|string|max:5000')]
    public string $consent_summary = '';

    #[Validate('nullable|string|max:5000')]
    public string $notes = '';

    #[Validate('accepted')]
    public bool $confirmed = false;

    public bool $submitted = false;
    public ?string $intakeUrl = null;

    public function mount(string $token): void
    {
        $this->record = ConsentRecord::findByToken($token)
            ?? abort(404, 'This consent link is not valid.');

        $this->intakeUrl = $this->record->appointment?->intakeSubmission?->getPublicUrl();

        if ($this->record->isComplete()) {
            $this->submitted = true;
            return;
        }

        $this->consent_given_by = $this->record->consent_given_by ?? '';
        $this->consent_summary  = $this->record->consent_summary ?? '';
        $this->notes            = $this->record->notes ?? '';
    }

    public function submit(): void
    {
        $this->validate();

        $this->record->update([
            'consent_given_by' => trim($this->consent_given_by),
            'consent_summary'  => trim($this->consent_summary),
            'notes'            => trim($this->notes),
            'status'           => 'complete',
            'signed_on'        => now(),
            'signed_at_ip'     => request()->ip(),
            'signed_at_user_agent' => request()->userAgent(),
        ]);

        AuditLogger::signed($this->record, ['consent_given_by' => trim($this->consent_given_by)]);

        $this->sendConsentSignedNotifications();

        $this->submitted = true;
    }

    private function sendConsentSignedNotifications(): void
    {
        try {
            $patient = $this->record->patient;
            $practitioner = $this->record->appointment?->practitioner?->user;

            if ($patient && $patient->email) {
                \Mail::to($patient->email)->send(
                    new \App\Mail\ConsentSignedMail($this->record, $patient)
                );
            }

            if ($practitioner && $practitioner->email) {
                \Mail::to($practitioner->email)->send(
                    new \App\Mail\PractitionerConsentSignedMail($this->record, $patient, $practitioner)
                );
            }
        } catch (\Exception $e) {
            // Log but don't fail — consent was signed successfully
            \Log::warning('Failed to send consent signed notification', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.public.consent-form');
    }
}
