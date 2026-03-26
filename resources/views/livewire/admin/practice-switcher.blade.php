@if($isSuperAdmin)
<div style="display:flex;align-items:center;gap:0.5rem;padding:0 1rem;border-left:1px solid #e2e8f0;margin-left:0.5rem;">
    <span style="font-size:0.6875rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap;">Practice</span>
    <select wire:change="switchTo($event.target.value)"
            style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.375rem 2rem 0.375rem 0.625rem;font-size:0.875rem;font-weight:500;color:#0f172a;cursor:pointer;outline:none;min-width:180px;max-width:260px;appearance:auto;">
        @foreach($practices as $practice)
            <option value="{{ $practice->id }}" {{ (int)$selectedPracticeId === $practice->id ? 'selected' : '' }}>
                {{ $practice->name }}
            </option>
        @endforeach
    </select>
    <span style="font-size:0.6875rem;background:#0d9488;color:#ffffff;padding:0.15rem 0.5rem;border-radius:9999px;font-weight:700;white-space:nowrap;letter-spacing:0.03em;">SUPER ADMIN</span>
</div>
@else
<div style="display:flex;align-items:center;gap:0.5rem;padding:0 1rem;border-left:1px solid #e2e8f0;margin-left:0.5rem;">
    <span style="font-size:0.6875rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Practice</span>
    <span style="font-size:0.875rem;font-weight:600;color:#0f172a;">{{ auth()->user()?->practice?->name ?? '—' }}</span>
</div>
@endif
