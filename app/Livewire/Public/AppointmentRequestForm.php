<?php

namespace App\Livewire\Public;

use App\Models\AppointmentRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.public', ['title' => 'Appointment Request'])]
class AppointmentRequestForm extends Component
{
    public AppointmentRequest $appointmentRequest;

    #[Validate('required|string|max:2000')]
    public string $preferred_times = '';

    #[Validate('nullable|string|max:2000')]
    public string $note = '';

    public bool $submitted = false;

    public function mount(string $token): void
    {
        $this->appointmentRequest = AppointmentRequest::findByToken($token)
            ?? abort(404, 'This appointment request link is not valid.');

        if ($this->appointmentRequest->status === AppointmentRequest::STATUS_PENDING) {
            $this->submitted = true;
        }
    }

    public function submit(): void
    {
        if ($this->appointmentRequest->status === AppointmentRequest::STATUS_PENDING) {
            $this->submitted = true;

            return;
        }

        $this->validate();

        $this->appointmentRequest->update([
            'preferred_times' => trim($this->preferred_times),
            'note' => trim($this->note),
            'status' => AppointmentRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public.appointment-request-form');
    }
}
