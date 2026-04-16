@component('mail::message')
# Patient Consent Form Signed

Hi {{ $practitioner->name }},

{{ $patient->name }} has signed the consent form for their appointment.

**Patient:** {{ $patient->name }}<br>
**Date Signed:** {{ $signedDate }}<br>
**Signed From:** {{ $record->signed_at_ip }}

You can now proceed with the scheduled appointment.

@component('mail::button', ['url' => route('filament.admin.resources.patients.view', $patient->id)])
View Patient Record
@endcomponent

Thanks,<br>
{{ $record->practice->name }} Team
@endcomponent
