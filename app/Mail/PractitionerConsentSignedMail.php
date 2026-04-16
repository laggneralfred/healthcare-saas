<?php

namespace App\Mail;

use App\Models\ConsentRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PractitionerConsentSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConsentRecord $record,
        public Patient $patient,
        public User $practitioner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Consent Form Signed - ' . $this->patient->name . ' - ' . $this->record->practice->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.practitioner-consent-signed',
            with: [
                'patient' => $this->patient,
                'practitioner' => $this->practitioner,
                'record' => $this->record,
                'signedDate' => $this->record->signed_on?->format('M j, Y \a\t g:i A'),
            ],
        );
    }
}
