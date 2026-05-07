<x-filament-widgets::widget>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="min-width:0;">
            <div style="font-size:13px;font-weight:800;color:#92400e;">HIPAA/BAA acknowledgement needed</div>
            <div style="font-size:12px;color:#92400e;line-height:1.45;margin-top:2px;">
                Before entering real patient or clinical data, please complete the HIPAA/BAA acknowledgement.
            </div>
        </div>
        <a href="{{ $acknowledgementUrl }}" style="font-size:12px;font-weight:800;color:#b45309;text-decoration:none;white-space:nowrap;">
            Review acknowledgement
        </a>
    </div>
</x-filament-widgets::widget>
