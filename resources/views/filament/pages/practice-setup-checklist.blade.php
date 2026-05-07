<x-filament-panels::page>
    @if(! ($practice ?? null))
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:32px;text-align:center;color:#6b7280;">
            No practice selected. Use the practice switcher in the top bar.
        </div>
    @else
        @include('filament.partials.practice-setup-checklist', ['setupChecklist' => $setupChecklist])
    @endif
</x-filament-panels::page>
