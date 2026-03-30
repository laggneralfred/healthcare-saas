<?php

namespace App\Notifications;

use App\Models\ExportToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ExportToken $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $downloadUrl = route('export.download', $this->token->id);
        $expiresAt = $this->token->expires_at->format('F j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject('Your Practiq data export is ready')
            ->greeting('Your data export is ready!')
            ->line("Your {$this->token->format} data export has been prepared and is ready to download.")
            ->line("Download link expires: {$expiresAt}")
            ->action('Download Export', $downloadUrl)
            ->line('Files are only available for 24 hours. Please download soon.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'token_id' => $this->token->id,
            'format' => $this->token->format,
            'download_url' => route('export.download', $this->token->id),
            'expires_at' => $this->token->expires_at->toIso8601String(),
        ];
    }
}
