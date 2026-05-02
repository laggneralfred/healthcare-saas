<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Models\Patient;
use App\Models\Practice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPortalFormController extends Controller
{
    public function index(Request $request): View
    {
        [$practice, $patient] = $this->portalContext($request);

        return view('patient.forms-index', [
            'practice' => $practice,
            'patient' => $patient,
            'formSubmissions' => $this->submissionsFor($practice->id, $patient->id)->get(),
        ]);
    }

    public function show(Request $request, FormSubmission $formSubmission): View|RedirectResponse
    {
        [$practice, $patient] = $this->portalContext($request);
        $submission = $this->authorizedSubmission($formSubmission, $practice->id, $patient->id);

        if (! $submission) {
            abort(404);
        }

        if ($submission->status !== FormSubmission::STATUS_PENDING) {
            return redirect()
                ->route('patient.forms.index')
                ->with('form_status', 'This form has already been submitted or is no longer available.');
        }

        return view('patient.form-show', [
            'practice' => $practice,
            'patient' => $patient,
            'submission' => $submission,
            'formTemplate' => $submission->formTemplate,
            'fields' => $this->fieldsFor($submission),
        ]);
    }

    public function store(Request $request, FormSubmission $formSubmission): RedirectResponse
    {
        [$practice, $patient] = $this->portalContext($request);
        $submission = $this->authorizedSubmission($formSubmission, $practice->id, $patient->id);

        if (! $submission) {
            abort(404);
        }

        if ($submission->status !== FormSubmission::STATUS_PENDING) {
            return redirect()
                ->route('patient.forms.index')
                ->with('form_status', 'This form has already been submitted or is no longer available.');
        }

        $validated = $request->validate($this->rulesFor($submission));

        $submission->update([
            'submitted_data_json' => $this->submittedDataFor($submission, $validated['fields'] ?? [], $request),
            'status' => FormSubmission::STATUS_SUBMITTED,
        ]);

        return redirect()
            ->route('patient.forms.index')
            ->with('form_status', 'Thank you. Your form has been submitted.');
    }

    private function portalContext(Request $request): array
    {
        $practiceId = (int) $request->session()->get('patient_portal_practice_id');
        $patientId = (int) $request->session()->get('patient_portal_patient_id');

        $practice = Practice::query()->findOrFail($practiceId);
        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->findOrFail($patientId);

        return [$practice, $patient];
    }

    private function submissionsFor(int $practiceId, int $patientId)
    {
        return FormSubmission::withoutPracticeScope()
            ->with('formTemplate')
            ->where('practice_id', $practiceId)
            ->where('patient_id', $patientId)
            ->latest()
            ->limit(10);
    }

    private function authorizedSubmission(FormSubmission $submission, int $practiceId, int $patientId): ?FormSubmission
    {
        return FormSubmission::withoutPracticeScope()
            ->with('formTemplate')
            ->whereKey($submission->id)
            ->where('practice_id', $practiceId)
            ->where('patient_id', $patientId)
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
