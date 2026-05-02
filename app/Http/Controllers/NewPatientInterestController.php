<?php

namespace App\Http\Controllers;

use App\Models\NewPatientInterest;
use App\Models\Practice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewPatientInterestController extends Controller
{
    public function create(): View
    {
        $practice = $this->resolvePractice();

        return view('public.new-patient-interest', [
            'practice' => $practice,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $practice = $this->resolvePractice();

        if (! $practice) {
            return redirect()->route('new-patient.unavailable');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'preferred_service' => ['nullable', 'string', 'max:255'],
            'preferred_days_times' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:4000'],
            'contact_acknowledgement' => ['accepted'],
        ]);

        unset($data['contact_acknowledgement']);

        NewPatientInterest::withoutPracticeScope()->create($data + [
            'practice_id' => $practice->id,
            'status' => NewPatientInterest::STATUS_NEW,
        ]);

        return redirect()->route('new-patient.thanks');
    }

    public function thanks(): View
    {
        return view('public.new-patient-thanks');
    }

    public function unavailable(): View
    {
        return view('public.new-patient-unavailable');
    }

    private function resolvePractice(): ?Practice
    {
        $query = Practice::query()
            ->where('is_active', true)
            ->where('is_demo', false);

        return $query->count() === 1 ? $query->first() : null;
    }
}
