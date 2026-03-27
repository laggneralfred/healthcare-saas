@if ($showBanner)
    <div x-data="{ dismissed: localStorage.getItem('practiq_trial_dismissed') === '1' }"
         x-show="!dismissed"
         x-transition
         style="background-color: #0D7377; color: white; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; font-size: 14px;">
        <span>
            <strong>⏱ {{ $daysRemaining }} day{{ $daysRemaining === 1 ? '' : 's' }} remaining</strong> in your free trial.
            <a href="{{ route('filament.admin.pages.billing') }}" style="color: #93fffa; text-decoration: underline; font-weight: 500;">
                Upgrade now
            </a>
        </span>
        <button @click="dismissed = true; localStorage.setItem('practiq_trial_dismissed', '1')"
                style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; padding: 0; margin: 0;"
                type="button">
            ×
        </button>
    </div>
@endif
