@if($isSuperAdmin && $practices->count() > 1)
<div style="display:flex;align-items:center;gap:0.5rem;padding:0 0.75rem;">
    <span style="font-size:0.75rem;font-weight:600;color:#6b7280;white-space:nowrap;text-transform:uppercase;letter-spacing:0.05em;">Practice</span>
    <select wire:model.live="selectedPracticeId"
            style="background:#f9fafb;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.5rem;font-size:0.875rem;color:#111827;cursor:pointer;outline:none;min-width:160px;">
        @foreach($practices as $practice)
            <option value="{{ $practice->id }}" @selected($selectedPracticeId === $practice->id)>
                {{ $practice->name }}
            </option>
        @endforeach
    </select>
</div>
@elseif(!$isSuperAdmin)
<div style="display:flex;align-items:center;padding:0 0.75rem;">
    <span style="font-size:0.875rem;font-weight:500;color:#374151;">{{ auth()->user()?->practice?->name }}</span>
</div>
@endif
