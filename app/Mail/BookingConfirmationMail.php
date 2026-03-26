<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\ConsentRecord;
use App\Models\IntakeSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly IntakeSubmission $intake,
        public readonly ConsentRecord $consent,
    ) {}

    public function envelope(): Envelope
    {
        $practice = $this->appointment->practice;

        return new Envelope(
            subject: "Your appointment at {$practice->name} is confirmed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmation',
        );
    }
}
