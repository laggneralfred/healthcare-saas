<?php

namespace App\Mail;

use App\Models\FormTemplate;
use App\Models\MessageLog;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExistingPatientFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Patient $patient,
        public readonly FormTemplate $formTemplate,
        public readonly MessageLog $messageLog,
        public readonly string $formUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@practiqapp.com');
        $fromName = $this->patient->practice?->name ?? config('mail.from.name', 'Practiq');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->messageLog->subject ?? 'Forms from '.$fromName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.existing-patient-form',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
