<x-filament-panels::page>
    @if(! ($practice ?? null))
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:32px;text-align:center;color:#6b7280;">
            No practice selected.
        </div>
    @else
        <section style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:18px;margin-bottom:16px;">
            <p style="margin:0 0 4px;font-size:12px;font-weight:800;text-transform:uppercase;color:#0f766e;">Setup readiness</p>
            <h2 style="margin:0;font-size:20px;font-weight:800;color:#111827;">HIPAA / BAA Acknowledgement</h2>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#475569;">
                Practiq helps practices manage patient and clinical information. Practices are responsible for using Practiq according to applicable privacy, security, professional, and healthcare laws.
            </p>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#475569;">
                If your practice stores protected health information in Practiq, a Business Associate Agreement may be required. This acknowledgement is part of setup readiness before entering real patient or clinical data.
            </p>
            <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:#6b7280;">
                This acknowledgement is not a full BAA signing or e-signature system, and this page is not legal advice.
            </p>
            <p style="margin:10px 0 0;font-size:13px;color:#6b7280;">
                Version {{ $documentVersion }} · <a href="{{ $documentUrl }}" target="_blank" rel="noopener" style="color:#0f766e;font-weight:800;text-decoration:none;">Read public page</a>
            </p>
        </section>

        @if($latestAcceptance)
            <section style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:14px;margin-bottom:16px;">
                <h3 style="margin:0;font-size:15px;font-weight:800;color:#065f46;">Acknowledgement recorded</h3>
                <p style="margin:5px 0 0;font-size:13px;color:#047857;">
                    Accepted {{ $latestAcceptance->accepted_at?->format('M j, Y g:i A') }}
                    @if($latestAcceptance->user?->name)
                        by {{ $latestAcceptance->user->name }}
                    @endif
                    for version {{ $latestAcceptance->document_version }}.
                </p>
            </section>
        @else
            <form wire:submit="submit" style="display:grid;gap:16px;">
                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                    <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;font-weight:700;color:#374151;">
                        <input type="checkbox" wire:model="acknowledged" style="margin-top:2px;">
                        <span>I acknowledge the HIPAA/BAA responsibilities described above.</span>
                    </label>
                    @error('acknowledged')
                        <div style="margin-top:8px;color:#b91c1c;font-size:13px;">Please acknowledge the HIPAA/BAA responsibilities before continuing.</div>
                    @enderror
                </section>

                <div>
                    <button type="submit" style="background:#0f766e;color:#ffffff;border:0;border-radius:6px;padding:9px 13px;font-size:13px;font-weight:800;cursor:pointer;">
                        Record acknowledgement
                    </button>
                </div>
            </form>
        @endif
    @endif
</x-filament-panels::page>
