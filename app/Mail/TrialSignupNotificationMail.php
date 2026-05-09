<?php

namespace App\Mail;

use App\Models\TrialSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialSignupNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TrialSignup $trialSignup) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Practiq trial signup',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.trial-signup-notification',
        );
    }
}
