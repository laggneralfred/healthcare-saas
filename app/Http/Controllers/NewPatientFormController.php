<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Models\NewPatientInterest;
use App\Services\PatientPortalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewPatientFormController extends Controller
{
    public function show(string $token, PatientPortalTokenService $tokens): View|RedirectResponse
    {
        $portalToken = $tokens->verifyNewPatientFormToken($token);

        if (! $portalToken) {
            return redirect()
                ->route('patient.portal.invalid')
                ->with('status', 'This form link is invalid or has expired.');
        }

        $submission = $this->pendingSubmissionFor($portalToken->practice_id, $portalToken->new_patient_interest_id);

        if (! $submission) {
            return redirect()
                ->route('patient.new-patient-form.thanks')
                ->with('status', 'Your forms have already been submitted or are no longer available.');
        }

        return view('patient.new-patient-form', [
            'token' => $token,
            'practice' => $submission->practice,
            'interest' => $submission->newPatientInterest,
            'submission' => $submission,
            'formTemplate' => $submission->formTemplate,
            'fields' => $this->fieldsFor($submission),
        ]);
    }

    public function store(string $token, Request $request, PatientPortalTokenService $tokens): RedirectResponse
    {
        $portalToken = $tokens->verifyNewPatientFormToken($token);

        if (! $portalToken) {
            return redirect()
                ->route('patient.portal.invalid')
                ->with('status', 'This form link is invalid or has expired.');
        }

        $submission = $this->pendingSubmissionFor($portalToken->practice_id, $portalToken->new_patient_interest_id);

        if (! $submission) {
            return redirect()
                ->route('patient.new-patient-form.thanks')
                ->with('status', 'Your forms have already been submitted or are no longer available.');
        }

        $validated = $request->validate($this->rulesFor($submission));
        $submitted = $this->submittedDataFor($submission, $validated['fields'] ?? [], $request);

        $submission->update([
            'submitted_data_json' => $submitted,
            'status' => FormSubmission::STATUS_SUBMITTED,
        ]);

        $submission->newPatientInterest?->update([
            'status' => NewPatientInterest::STATUS_REVIEWING,
        ]);

        return redirect()->route('patient.new-patient-form.thanks');
    }

    public function thanks(): View
    {
        return view('patient.new-patient-form-thanks');
    }

    private function pendingSubmissionFor(int $practiceId, ?int $interestId): ?FormSubmission
    {
        if (! $interestId) {
            return null;
        }

        return FormSubmission::withoutPracticeScope()
            ->with(['practice', 'newPatientInterest', 'formTemplate'])
            ->where('practice_id', $practiceId)
            ->where('new_patient_interest_id', $interestId)
            ->where('status', FormSubmission::STATUS_PENDING)
            ->latest()
            ->first();
    }

    private function fieldsFor(FormSubmission $submission): array
    {
        return $submission->formTemplate->schema_json['fields'] ?? [];
    }

    private function rulesFor(FormSubmission $submission): array
    {
        $rules = [];

        foreach ($this->fieldsFor($submission) as $field) {
            $name = $field['name'] ?? null;

            if (! $name) {
                continue;
            }

            $type = $field['type'] ?? 'text';
            $required = (bool) ($field['required'] ?? false);
            $fieldRules = [$required ? 'required' : 'nullable'];

            if ($type === 'checkbox') {
                $fieldRules = [$required ? 'accepted' : 'nullable'];
            } elseif ($type === 'date') {
                $fieldRules[] = 'date';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:5000';
            }

            $rules['fields.'.$name] = $fieldRules;
        }

        return $rules;
    }

    private function submittedDataFor(FormSubmission $submission, array $validatedFields, Request $request): array
    {
        $data = [];

        foreach ($this->fieldsFor($submission) as $field) {
            $name = $field['name'] ?? null;

            if (! $name) {
                continue;
            }

            if (($field['type'] ?? 'text') === 'checkbox') {
                $data[$name] = $request->boolean('fields.'.$name);

                continue;
            }

            $data[$name] = $validatedFields[$name] ?? null;
        }

        return $data;
    }
}
