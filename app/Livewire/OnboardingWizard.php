<?php

namespace App\Livewire;

use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public', ['title' => 'Practice Setup — Practiq'])]
class OnboardingWizard extends Component
{
    public int $currentStep = 1;
    public ?Practice $practice = null;

    public string $practiceName = '';
    public string $practiceAddress = '';
    public string $practicePhone = '';
    public string $practiceWebsite = '';

    public string $practitionerName = '';
    public string $licenseNumber = '';
    public string $discipline = '';

    public array $disciplines = [];

    public bool $setupLegalLater = true;

    protected $rules = [
        'practiceName' => 'required|string|max:255',
        'practicePhone' => 'required|regex:/^\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/',
        'practiceWebsite' => 'nullable|url',
        'practitionerName' => 'required|string|max:255',
        'licenseNumber' => 'required|string|max:255',
        'discipline' => 'required|in:acupuncture,massage,chiropractic,physiotherapy',
        'disciplines' => 'required|array|min:1',
    ];

    public function mount(): void
    {
        $practice = auth()->user()->practice;

        if ($practice) {
            if ($practice->setup_completed_at) {
                $this->redirect('/admin/dashboard');
                return;
            }
            $this->practice = $practice;
            $this->practiceName = $practice->name;
            $this->discipline = $practice->discipline ?? 'acupuncture';
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
            'practiceName' => 'required|string|max:255',
            'practitionerName' => 'required|string|max:255',
            'discipline' => 'required|in:acupuncture,massage,chiropractic,physiotherapy',
            'disciplines' => 'required|array|min:1',
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
                'name' => $this->practiceName,
                'slug' => $slug,
                'timezone' => 'UTC',
                'is_active' => true,
                'discipline' => $this->discipline,
                'trial_ends_at' => now()->addDays(30),
                'setup_completed_at' => now(),
            ]);

            auth()->user()->update(['practice_id' => $this->practice->id]);
        } else {
            $this->practice->update([
                'name' => $this->practiceName,
                'discipline' => $this->discipline,
                'setup_completed_at' => now(),
            ]);
        }

        $practitioner = Practitioner::firstOrCreate(
            [
                'practice_id' => $this->practice->id,
                'user_id' => auth()->id(),
            ],
            [
                'practice_id' => $this->practice->id,
                'user_id' => auth()->id(),
                'license_number' => $this->licenseNumber,
                'specialty' => $this->practitionerName,
                'is_active' => true,
            ]
        );

        $practitioner->update([
            'license_number' => $this->licenseNumber,
            'specialty' => $this->practitionerName,
        ]);

        $this->redirect('/admin');
    }

    private function validateStep1(): void
    {
        $this->validate([
            'practiceName' => 'required|string|max:255',
        ]);
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
            'practitionerName' => 'required|string|max:255',
            'licenseNumber' => 'required|string|max:255',
            'discipline' => 'required|in:acupuncture,massage,chiropractic,physiotherapy',
        ]);
    }

    private function validateStep4(): void
    {
        $this->validate([
            'disciplines' => 'required|array|min:1',
        ]);
    }

    private function validateStep5(): void
    {
        // Step 5 is just a choice, no validation needed
    }

    public function render()
    {
        return view('livewire.onboarding-wizard');
    }
}
