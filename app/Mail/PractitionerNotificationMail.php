<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PractitionerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        $patient  = $this->appointment->patient;
        $practice = $this->appointment->practice;

        return new Envelope(
            subject: "New booking: {$patient->name} at {$practice->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.practitioner-notification',
        );
    }
}
