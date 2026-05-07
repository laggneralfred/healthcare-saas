@if($setupChecklist ?? null)
    @php
        $completeCount = $setupChecklist['complete_count'];
        $totalCount = $setupChecklist['total_count'];
    @endphp

    <section style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:16px;margin-bottom:16px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0;font-size:16px;font-weight:800;color:#111827;">Setup Checklist</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                    Configure these basics before advertising public links or relying on scheduling suggestions.
                </p>
            </div>
            <div style="font-size:13px;font-weight:800;color:{{ $setupChecklist['is_complete'] ? '#047857' : '#92400e' }};background:{{ $setupChecklist['is_complete'] ? '#ecfdf5' : '#fffbeb' }};border:1px solid {{ $setupChecklist['is_complete'] ? '#a7f3d0' : '#fde68a' }};border-radius:9999px;padding:5px 10px;">
                {{ $completeCount }} of {{ $totalCount }} complete
            </div>
        </div>

        <div style="display:grid;gap:10px;margin-top:14px;">
            @foreach($setupChecklist['items'] as $item)
                <div style="display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:10px;align-items:start;border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:{{ $item['complete'] ? '#f9fafb' : '#ffffff' }};">
                    <div aria-hidden="true" style="width:22px;height:22px;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;background:{{ $item['complete'] ? '#d1fae5' : '#fef3c7' }};color:{{ $item['complete'] ? '#065f46' : '#92400e' }};">
                        {{ $item['complete'] ? '✓' : '!' }}
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:13px;font-weight:800;color:#111827;">{{ $item['label'] }}</div>
                        <div style="font-size:12px;color:#6b7280;line-height:1.45;margin-top:2px;">{{ $item['explanation'] }}</div>
                        @if(! empty($item['warning']))
                            <div style="font-size:12px;color:#92400e;line-height:1.45;margin-top:6px;font-weight:700;">
                                {{ $item['warning'] }}
                            </div>
                        @endif
                    </div>
                    <a href="{{ $item['action_url'] }}" style="font-size:12px;font-weight:800;color:#2563eb;text-decoration:none;white-space:nowrap;">
                        {{ $item['action_label'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
