@component('mail::message')
# Consent Form Signed

Hi {{ $patient->name }},

Thank you for signing the consent form for your appointment at {{ $record->practice->name }}.

**Signed on:** {{ $signedDate }}

If you have any questions or need to make changes to your consent, please contact us directly.

We look forward to seeing you soon!

@component('mail::button', ['url' => route('home')])
View Your Account
@endcomponent

Thanks,<br>
{{ $record->practice->name }} Team
@endcomponent
