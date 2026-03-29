@php
    $daysRemaining = $daysRemaining ?? 0;
    $isUrgent      = $daysRemaining <= 7;
    $bgColor       = $isUrgent ? '#dc2626' : '#d97706';
@endphp
<div x-data="{ dismissed: localStorage.getItem('practiq_trial_dismissed') === '1' }"
     x-show="!dismissed"
     x-transition
     style="background-color: {{ $bgColor }}; color: white; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
    <span>
        <strong>⏱ {{ $daysRemaining }} day{{ $daysRemaining === 1 ? '' : 's' }} remaining</strong> in your free trial.
        <a href="{{ route('filament.admin.pages.billing') }}"
           style="color: white; text-decoration: underline; font-weight: 600; margin-left: 6px;">
            Upgrade now &rarr;
        </a>
    </span>
    <button @click="dismissed = true; localStorage.setItem('practiq_trial_dismissed', '1')"
            style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; line-height: 1; padding: 0; margin: 0;"
            type="button"
            aria-label="Dismiss">
        &times;
    </button>
</div>
