<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization will be handled by Filament's authorization
        return true;
    }

    public function rules(): array
    {
        return [
            'practice_id'     => ['required', 'integer', Rule::exists('practices', 'id')],
            'appointment_id'  => ['required', 'integer', Rule::exists('appointments', 'id')->unique('checkout_sessions')],
            'patient_id'      => ['required', 'integer', Rule::exists('patients', 'id')],
            'practitioner_id' => ['nullable', 'integer', Rule::exists('practitioners', 'id')],
            'charge_label'    => ['required', 'string', 'max:255'],
            'amount_total'    => ['required', 'numeric', 'min:0'],
            'amount_paid'     => ['required', 'numeric', 'min:0', 'lte:amount_total'],
            'tender_type'     => ['nullable', 'string', 'in:cash,card,check,other'],
            'state'           => ['nullable', 'string', 'in:draft,open,paid,payment_due,voided'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'payment_note'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.unique' => 'This appointment already has a checkout session.',
            'amount_paid.lte'       => 'Amount paid cannot exceed total amount.',
        ];
    }
}
