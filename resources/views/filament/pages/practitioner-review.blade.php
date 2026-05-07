<x-filament-panels::page>
    @if(! ($practice ?? null))
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:32px;text-align:center;color:#6b7280;">
            No practice selected. Use the practice switcher in the top bar.
        </div>
    @else
        <section style="background:#ffffff;border:1px solid #ccfbf1;border-radius:8px;padding:18px;margin-bottom:16px;">
            <p style="margin:0 0 4px;font-size:12px;font-weight:800;text-transform:uppercase;color:#0f766e;">Founding Practitioner Review Program</p>
            <h2 style="margin:0;font-size:20px;font-weight:800;color:#111827;">Help shape Practiq</h2>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#475569;">
                Your feedback helps shape Practiq for solo and small health practices. Eligible practitioners who complete the review and subscribe may receive 50% off their first 3 paid months. This is not a lifetime discount, and submitting feedback does not require subscription.
            </p>
            <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:#6b7280;">
                This questionnaire is feedback and research only. It is not legal signup paperwork and does not change billing or subscription status.
            </p>
        </section>

        @if($latestSubmission)
            <section style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:16px;">
                <h3 style="margin:0;font-size:15px;font-weight:800;color:#111827;">Latest submitted response</h3>
                <p style="margin:5px 0 0;font-size:13px;color:#64748b;">
                    Submitted {{ $latestSubmission->submitted_at?->format('M j, Y g:i A') ?? $latestSubmission->created_at?->format('M j, Y g:i A') }}
                    @if($latestSubmission->user?->name)
                        by {{ $latestSubmission->user->name }}
                    @endif
                </p>
                @if($latestSubmission->most_useful)
                    <p style="margin:10px 0 0;font-size:13px;color:#334155;line-height:1.5;">Most useful: {{ $latestSubmission->most_useful }}</p>
                @endif
                <p style="margin:10px 0 0;font-size:12px;color:#64748b;">Submitting this form again will create another review response.</p>
            </section>
        @endif

        <form wire:submit="submit" style="display:grid;gap:16px;">
            <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#111827;">Practice context</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Practice type
                        <input type="text" wire:model="practice_type" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Clinic size
                        <input type="text" wire:model="clinic_size" placeholder="Solo, 2-5 practitioners, staff-supported..." style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                </div>
                <label style="display:grid;gap:5px;margin-top:12px;font-size:13px;font-weight:700;color:#374151;">
                    Current system / tools
                    <textarea wire:model="current_systems" rows="3" placeholder="Jane, SimplePractice, paper notes, Google Calendar..." style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                </label>
            </section>

            <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#111827;">First week experience</h3>
                <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                    First impression
                    <textarea wire:model="first_impression" rows="3" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                </label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px;">
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Was it clear what to do first?
                        <select wire:model="setup_clarity_rating" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                            <option value="">Choose a rating</option>
                            <option value="1">1 - Not clear</option>
                            <option value="2">2</option>
                            <option value="3">3 - Somewhat clear</option>
                            <option value="4">4</option>
                            <option value="5">5 - Very clear</option>
                        </select>
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Setup checklist helpfulness
                        <input type="text" wire:model="setup_checklist_helpfulness" placeholder="Helpful, partly helpful, not useful..." style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                </div>
                <label style="display:grid;gap:5px;margin-top:12px;font-size:13px;font-weight:700;color:#374151;">
                    Which setup step was most confusing?
                    <input type="text" wire:model="confusing_setup_step" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                </label>
            </section>

            <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#111827;">Core workflows</h3>
                <div style="display:grid;gap:12px;">
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Did website links make sense?
                        <input type="text" wire:model="website_links_feedback" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Online scheduling preference
                        <input type="text" wire:model="scheduling_preference" placeholder="Request-only, staff-confirmed, self-booking later..." style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Online intake forms feedback
                        <input type="text" wire:model="online_forms_feedback" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Notes workflow
                        <input type="text" wire:model="notes_workflow" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        AI assistance feedback
                        <input type="text" wire:model="ai_feedback" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Follow-up workflow feedback
                        <input type="text" wire:model="follow_up_feedback" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                </div>
            </section>

            <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#111827;">Subscription fit</h3>
                <div style="display:grid;gap:12px;">
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        What would make you more likely to subscribe?
                        <input type="text" wire:model="pricing_feedback" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Subscription blockers
                        <textarea wire:model="subscription_blockers" rows="3" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Most useful part
                        <textarea wire:model="most_useful" rows="2" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        Most confusing part
                        <textarea wire:model="most_confusing" rows="2" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                    </label>
                    <label style="display:grid;gap:5px;font-size:13px;font-weight:700;color:#374151;">
                        What would make the first week easier?
                        <textarea wire:model="one_change" rows="2" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;"></textarea>
                    </label>
                </div>
            </section>

            <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                <h3 style="margin:0 0 12px;font-size:16px;font-weight:800;color:#111827;">Contact and discount acknowledgement</h3>
                <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;font-weight:700;color:#374151;">
                    <input type="checkbox" wire:model="may_contact" style="margin-top:2px;">
                    May I contact you with follow-up questions?
                </label>
                <label style="display:grid;gap:5px;margin-top:12px;font-size:13px;font-weight:700;color:#374151;">
                    Contact info
                    <input type="text" wire:model="contact_info" placeholder="Email or phone, optional" style="border:1px solid #d1d5db;border-radius:6px;padding:8px;color:#111827;">
                </label>
                <label style="display:flex;gap:8px;align-items:flex-start;margin-top:12px;font-size:13px;font-weight:700;color:#374151;">
                    <input type="checkbox" wire:model="discount_acknowledged" style="margin-top:2px;">
                    <span>I understand the review discount, if eligible and I choose to subscribe, may be 50% off the first 3 paid months only. It is not a lifetime discount and submitting feedback does not require subscription.</span>
                </label>
                @error('discount_acknowledged')
                    <div style="margin-top:8px;color:#b91c1c;font-size:13px;">Please acknowledge the review discount terms before submitting.</div>
                @enderror
            </section>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button type="submit" style="background:#0f766e;color:#ffffff;border:0;border-radius:6px;padding:9px 13px;font-size:13px;font-weight:800;cursor:pointer;">
                    Submit review
                </button>
                <span wire:loading wire:target="submit" style="font-size:13px;color:#2563eb;">Submitting...</span>
            </div>
        </form>
    @endif
</x-filament-panels::page>
