<?php

namespace App\Mail;

use App\Models\ConsentRecord;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsentSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConsentRecord $record,
        public Patient $patient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Consent Form Signed - ' . $this->record->practice->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-signed',
            with: [
                'patient' => $this->patient,
                'record' => $this->record,
                'signedDate' => $this->record->signed_on?->format('M j, Y \a\t g:i A'),
            ],
        );
    }
}
