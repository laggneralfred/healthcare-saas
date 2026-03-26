<?php

namespace App\Livewire\Public;

use App\Models\IntakeSubmission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.public', ['title' => 'Patient Intake Form'])]
class IntakeForm extends Component
{
    public IntakeSubmission $submission;

    #[Validate('nullable|string|max:5000')]
    public string $reason_for_visit = '';

    #[Validate('nullable|string|max:5000')]
    public string $current_concerns = '';

    #[Validate('nullable|string|max:5000')]
    public string $relevant_history = '';

    #[Validate('nullable|string|max:5000')]
    public string $medications = '';

    #[Validate('nullable|string|max:5000')]
    public string $notes = '';

    public bool $submitted = false;
    public ?string $consentUrl = null;

    public function mount(string $token): void
    {
        $this->submission = IntakeSubmission::findByToken($token)
            ?? abort(404, 'This intake link is not valid.');

        $this->consentUrl = $this->submission->appointment?->consentRecord?->getPublicUrl();

        if ($this->submission->isComplete()) {
            $this->submitted = true;
            return;
        }

        // Pre-fill any existing partial data
        $this->reason_for_visit  = $this->submission->reason_for_visit ?? '';
        $this->current_concerns  = $this->submission->current_concerns ?? '';
        $this->relevant_history  = $this->submission->relevant_history ?? '';
        $this->medications       = $this->submission->medications ?? '';
        $this->notes             = $this->submission->notes ?? '';
    }

    public function submit(): void
    {
        $this->validate();

        $fields = [
            'reason_for_visit' => trim($this->reason_for_visit),
            'current_concerns' => trim($this->current_concerns),
            'relevant_history' => trim($this->relevant_history),
            'medications'      => trim($this->medications),
            'notes'            => trim($this->notes),
        ];

        if (empty(array_filter($fields))) {
            $this->addError('reason_for_visit', 'Please fill in at least one field before submitting.');
            return;
        }

        $this->submission->update(array_merge($fields, [
            'status'       => 'complete',
            'submitted_on' => now(),
        ]));

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public.intake-form');
    }
}
