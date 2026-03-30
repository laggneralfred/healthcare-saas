<x-filament-panels::page>
    <div style="max-width: 64rem; margin: 0 auto;">
        <!-- Description -->
        <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 1.5rem; border: 1px solid #e5e7eb; margin-bottom: 2rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Download Your Data</h2>
            <p style="color: #4b5563; margin-bottom: 0.75rem;">
                Export a complete copy of all your practice data including patients, appointments, clinical notes, and billing records. Your data is yours to keep.
            </p>
            <p style="color: #dc2626; font-size: 0.875rem;">
                <strong>⚠ Important:</strong> Export files are available for 24 hours after generation. Please download your file soon as links expire and are automatically deleted.
            </p>
        </div>

        <!-- Recent Exports -->
        @if($recentExports->isNotEmpty())
            <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 1.5rem; border: 1px solid #e5e7eb;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Recent Exports</h3>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #111827;">Format</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #111827;">Status</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #111827;">Requested</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #111827;">Expires</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #111827;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentExports as $export)
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: left;">
                                        <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                            {{ strtoupper($export->format) }}
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: left;">
                                        @if($export->status === 'processing')
                                            <span style="display: inline-block; background-color: #f3f4f6; color: #4b5563; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                                Processing...
                                            </span>
                                        @elseif($export->status === 'ready')
                                            <span style="display: inline-block; background-color: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                                Ready
                                            </span>
                                        @elseif($export->status === 'downloaded')
                                            <span style="display: inline-block; background-color: #f3f4f6; color: #4b5563; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                                Downloaded
                                            </span>
                                        @elseif($export->status === 'expired')
                                            <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                                Expired
                                            </span>
                                        @elseif($export->status === 'failed')
                                            <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 500;">
                                                Failed
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.75rem; text-align: left; color: #4b5563;">
                                        {{ $export->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td style="padding: 0.75rem; text-align: left; color: #4b5563;">
                                        {{ $export->expires_at->format('M d, Y H:i') }}
                                    </td>
                                    <td style="padding: 0.75rem; text-align: left;">
                                        @if($export->status === 'ready' && !$export->isExpired())
                                            <a href="{{ route('export.download', $export->id) }}" style="color: #0D7377; text-decoration: none; font-weight: 500; border-bottom: 1px solid #0D7377;">
                                                Download
                                            </a>
                                        @else
                                            <span style="color: #9ca3af;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
