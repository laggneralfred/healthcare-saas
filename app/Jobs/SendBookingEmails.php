<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Mail\PractitionerNotificationMail;
use App\Models\Appointment;
use App\Models\ConsentRecord;
use App\Models\IntakeSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly IntakeSubmission $intake,
        public readonly ConsentRecord $consent,
    ) {}

    public function handle(): void
    {
        // Patient confirmation with intake + consent links
        Mail::to($this->appointment->patient->email)
            ->send(new BookingConfirmationMail(
                $this->appointment,
                $this->intake,
                $this->consent,
            ));

        // Practitioner notification
        $practitionerEmail = $this->appointment->practitioner->user->email;
        Mail::to($practitionerEmail)
            ->send(new PractitionerNotificationMail($this->appointment));
    }
}
