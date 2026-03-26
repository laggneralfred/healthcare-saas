<?php

namespace App\Livewire\Admin;

use App\Models\Practice;
use App\Services\PracticeContext;
use Livewire\Component;

class PracticeSwitcher extends Component
{
    public ?int $selectedPracticeId = null;

    public function mount(): void
    {
        $this->selectedPracticeId = PracticeContext::currentPracticeId();
    }

    public function updatedSelectedPracticeId(?string $value): void
    {
        if ($value) {
            PracticeContext::setCurrentPracticeId((int) $value);
        }
        // Reload the current page so all resource queries re-run with the new context
        $this->redirect(request()->header('referer') ?? url()->current());
    }

    public function render()
    {
        return view('livewire.admin.practice-switcher', [
            'practices'    => Practice::orderBy('name')->get(['id', 'name']),
            'isSuperAdmin' => PracticeContext::isSuperAdmin(),
        ]);
    }
}
