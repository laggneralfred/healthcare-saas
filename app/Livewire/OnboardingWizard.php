<?php

namespace App\Livewire;

use App\Models\CommunicationRule;
use App\Models\MessageTemplate;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Support\PracticeAccessRoles;
use App\Support\PracticeType;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public', ['title' => 'Practice Setup — Practiq'])]
class OnboardingWizard extends Component
{
    public int $currentStep = 1;
    public ?Practice $practice = null;

    // Step 2 — Practice Details
    public string $practiceName = '';
    public string $practiceAddress = '';
    public string $practicePhone = '';
    public string $practiceWebsite = '';

    // Step 3 — Default Settings
    public int $defaultAppointmentDuration = 30;
    public int $defaultReminderHours = 24;
    public string $timezone = 'America/Los_Angeles';

    // Step 4 — Profile
    public string $practitionerName = '';
    public string $licenseNumber = '';
    public string $discipline = '';
    public string $practiceType = PracticeType::GENERAL_WELLNESS;

    public bool $setupLegalLater = true;

    protected $rules = [
        'practiceName'               => 'required|string|max:255',
        'practicePhone'              => 'required|regex:/^\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/',
        'practiceWebsite'            => 'nullable|url',
        'defaultAppointmentDuration' => 'required|in:15,30,45,60',
        'defaultReminderHours'       => 'required|in:0,24,48',
        'timezone'                   => 'required|string|max:100',
        'practitionerName'           => 'required|string|max:255',
        'licenseNumber'              => 'required|string|max:255',
        'practiceType'               => 'required|in:general_wellness,tcm_acupuncture,five_element_acupuncture,chiropractic,massage_therapy,physiotherapy',
    ];

    public function mount(): void
    {
        $practice = auth()->user()->practice;

        if ($practice) {
            if ($practice->setup_completed_at) {
                $this->redirect('/admin/dashboard');
                return;
            }
            $this->practice    = $practice;
            $this->practiceName = $practice->name;
            $this->practiceType = PracticeType::fromPractice($practice);
            $this->discipline   = PracticeType::disciplineFallback($this->practiceType);
            $this->timezone     = $practice->timezone ?? 'America/Los_Angeles';
        }
    }

    public function nextStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => $this->validateStep3(),
            4 => $this->validateStep4(),
            5 => $this->validateStep5(),
            default => null,
        };

        if ($this->currentStep < 6) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function skipOnboarding(): void
    {
        $this->redirect('/admin/dashboard');
    }

    public function skipLegalSetup(): void
    {
        $this->setupLegalLater = true;
        $this->completeSetup();
    }

    public function proceedWithLegalSetup(): void
    {
        $this->setupLegalLater = false;
        $this->currentStep = 6;
    }

    public function completeSetup(): void
    {
        $this->validate([
            'practiceName'               => 'required|string|max:255',
            'practitionerName'           => 'required|string|max:255',
            'practiceType'               => 'required|in:general_wellness,tcm_acupuncture,five_element_acupuncture,chiropractic,massage_therapy,physiotherapy',
            'defaultAppointmentDuration' => 'required|in:15,30,45,60',
            'defaultReminderHours'       => 'required|in:0,24,48',
            'timezone'                   => 'required|string|max:100',
        ]);

        if (! $this->practice) {
            $slug = Str::slug($this->practiceName);
            $base = $slug;
            $n = 2;
            while (Practice::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$n}";
                $n++;
            }

            $this->practice = Practice::create([
                'name'                        => $this->practiceName,
                'slug'                        => $slug,
                'timezone'                    => $this->timezone,
                'is_active'                   => true,
                'discipline'                  => PracticeType::disciplineFallback($this->practiceType),
                'practice_type'               => $this->practiceType,
                'trial_ends_at'               => now()->addDays(30),
                'setup_completed_at'          => now(),
                'default_appointment_duration' => $this->defaultAppointmentDuration,
                'default_reminder_hours'       => $this->defaultReminderHours,
            ]);

            auth()->user()->update(['practice_id' => $this->practice->id]);
        } else {
            $this->practice->update([
                'name'                        => $this->practiceName,
                'discipline'                  => PracticeType::disciplineFallback($this->practiceType),
                'practice_type'               => $this->practiceType,
                'timezone'                    => $this->timezone,
                'setup_completed_at'          => now(),
                'default_appointment_duration' => $this->defaultAppointmentDuration,
                'default_reminder_hours'       => $this->defaultReminderHours,
            ]);
        }

        $user = auth()->user()->fresh();

        if ($user && (int) $user->practice_id === (int) $this->practice->id) {
            PracticeAccessRoles::assignOwner($user);
        }

        PracticeAccessRoles::ensurePracticeHasOwner($this->practice);

        $practitioner = Practitioner::firstOrCreate(
            ['practice_id' => $this->practice->id, 'user_id' => auth()->id()],
            [
                'practice_id'    => $this->practice->id,
                'user_id'        => auth()->id(),
                'license_number' => $this->licenseNumber,
                'specialty'      => $this->practitionerName,
                'is_active'      => true,
            ]
        );

        $practitioner->update([
            'license_number' => $this->licenseNumber,
            'specialty'      => $this->practitionerName,
        ]);

        $this->seedDefaultTemplates();

        $this->redirect('/admin/dashboard');
    }

    private function seedDefaultTemplates(): void
    {
        if (MessageTemplate::where('practice_id', $this->practice->id)->exists()) {
            return;
        }

        $reminderTemplate = MessageTemplate::create([
            'practice_id'   => $this->practice->id,
            'name'          => 'Appointment Reminder',
            'channel'       => 'email',
            'trigger_event' => 'appointment_reminder',
            'subject'       => 'Appointment Reminder',
            'body'          => 'Hi {patient_name}, this is a reminder of your appointment on {appointment_date} at {appointment_time} with {practitioner_name}. Reply STOP to opt out.',
            'is_active'     => true,
            'is_default'    => true,
        ]);

        MessageTemplate::create([
            'practice_id'   => $this->practice->id,
            'name'          => 'Appointment Confirmation',
            'channel'       => 'email',
            'trigger_event' => 'appointment_confirmation',
            'subject'       => 'Appointment Confirmed',
            'body'          => 'Hi {patient_name}, your appointment on {appointment_date} at {appointment_time} has been confirmed. See you soon!',
            'is_active'     => true,
            'is_default'    => true,
        ]);

        MessageTemplate::create([
            'practice_id'   => $this->practice->id,
            'name'          => 'New Patient Welcome',
            'channel'       => 'email',
            'trigger_event' => 'new_patient',
            'subject'       => 'Welcome to {practice_name}!',
            'body'          => 'Welcome to {practice_name}, {patient_name}! We look forward to seeing you on {appointment_date}.',
            'is_active'     => true,
            'is_default'    => true,
        ]);

        if ($this->defaultReminderHours > 0) {
            CommunicationRule::create([
                'practice_id'          => $this->practice->id,
                'message_template_id'  => $reminderTemplate->id,
                'is_active'            => true,
                'send_at_offset_minutes' => -($this->defaultReminderHours * 60),
            ]);
        }
    }

    private function validateStep1(): void
    {
        $this->validate(['practiceName' => 'required|string|max:255']);
    }

    private function validateStep2(): void
    {
        $this->validate([
            'practicePhone' => 'required|regex:/^\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/',
        ]);
    }

    private function validateStep3(): void
    {
        $this->validate([
            'defaultAppointmentDuration' => 'required|in:15,30,45,60',
            'defaultReminderHours'       => 'required|in:0,24,48',
            'timezone'                   => 'required|string|max:100',
        ]);
    }

    private function validateStep4(): void
    {
        $this->validate([
            'practitionerName' => 'required|string|max:255',
            'licenseNumber'    => 'required|string|max:255',
            'practiceType'     => 'required|in:general_wellness,tcm_acupuncture,five_element_acupuncture,chiropractic,massage_therapy,physiotherapy',
        ]);
    }

    private function validateStep5(): void
    {
        // Step 5 is the legal-forms choice — no input validation needed
    }

    public function render()
    {
        return view('livewire.onboarding-wizard');
    }
}
