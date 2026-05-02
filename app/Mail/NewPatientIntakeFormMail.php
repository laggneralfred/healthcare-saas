<?php

namespace App\Mail;

use App\Models\FormTemplate;
use App\Models\NewPatientInterest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPatientIntakeFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly NewPatientInterest $interest,
        public readonly FormTemplate $formTemplate,
        public readonly string $formUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@practiqapp.com');
        $fromName = $this->interest->practice?->name ?? config('mail.from.name', 'Practiq');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'New patient forms from '.$fromName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-patient-intake-form',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
