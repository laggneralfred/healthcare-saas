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

    public function switchTo(string $practiceId): void
    {
        $id = (int) $practiceId;
        if ($id > 0) {
            $this->selectedPracticeId = $id;
            PracticeContext::setCurrentPracticeId($id);
        }
        // Full page reload so all resource queries re-run with the new practice context
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
