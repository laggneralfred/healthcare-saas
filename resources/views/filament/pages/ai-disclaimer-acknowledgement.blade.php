<x-filament-panels::page>
    <div style="display:grid;gap:1rem;max-width:880px;">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:1.25rem;">
            <p style="margin:0 0 .75rem;color:#4b5563;">
                AI features in Practiq are optional support tools. They can help draft, summarize, translate, or review information,
                but AI-generated content may be incomplete, inaccurate, or inappropriate.
            </p>

            <ul style="margin:.75rem 0 0;padding-left:1.25rem;color:#374151;line-height:1.65;">
                <li>Practitioners must review all AI output before using it.</li>
                <li>Practitioners remain responsible for clinical judgment, documentation, diagnosis, treatment, communication, and patient care.</li>
                <li>AI output is not medical advice and should not be used as the sole basis for clinical decisions.</li>
                <li>Do not enter information into AI features unless the practice is authorized to do so and understands its privacy and security responsibilities.</li>
                <li>This acknowledgement is not legal advice.</li>
            </ul>

            <p style="margin:1rem 0 0;color:#6b7280;font-size:.875rem;">
                Version {{ $documentVersion }}
                @if ($documentUrl)
                    · <a href="{{ $documentUrl }}" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:none;font-weight:700;">View public disclaimer</a>
                @endif
            </p>
        </div>

        @if ($latestAcceptance)
            <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:1rem;color:#065f46;">
                <h3 style="font-weight:800;margin:0 0 .35rem;">Acknowledgement recorded</h3>
                <p style="margin:0;">
                    Accepted {{ optional($latestAcceptance->accepted_at)->format('M j, Y g:i A') }}
                    @if ($latestAcceptance->user)
                        by {{ $latestAcceptance->user->name }}
                    @endif
                    for version {{ $latestAcceptance->document_version }}.
                </p>
            </div>
        @else
            <form wire:submit="submit" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:1rem;">
                <label style="display:flex;gap:.65rem;align-items:flex-start;color:#7c2d12;font-weight:700;">
                    <input type="checkbox" wire:model="acknowledged" style="margin-top:.25rem;">
                    <span>I acknowledge that AI output must be reviewed and that I remain responsible for clinical decisions, documentation, and patient care.</span>
                </label>

                @error('acknowledged')
                    <p style="margin:.75rem 0 0;color:#b91c1c;font-weight:700;">{{ $message }}</p>
                @enderror

                <button type="submit" style="margin-top:1rem;background:#ea580c;color:white;border:0;border-radius:6px;padding:.6rem .9rem;font-weight:800;cursor:pointer;">
                    Record acknowledgement
                </button>
            </form>
        @endif
    </div>
</x-filament-panels::page>
