<?php

namespace App\Mail;

use App\Models\MessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteBackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MessageLog $messageLog,
        public readonly ?string $requestUrl = null,
    )
    {
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'noreply@practiqapp.com');
        $fromName = $this->messageLog->practice?->name ?? config('mail.from.name', 'Practiq');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->messageLog->subject ?? 'Checking in',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invite-back',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
