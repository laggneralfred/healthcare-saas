@if ($isSuperAdmin)
    <form method="POST" action="{{ route('admin.switch-practice') }}" style="display:inline-flex; align-items:center; margin-right:1rem;">
        @csrf
        <label for="practice-switcher" style="font-size:0.75rem; color:#6b7280; margin-right:0.4rem; white-space:nowrap;">Practice:</label>
        <select
            id="practice-switcher"
            name="practice_id"
            onchange="this.form.submit()"
            style="font-size:0.8rem; padding:0.2rem 0.5rem; border:1px solid #d1d5db; border-radius:4px; background:#fff; color:#374151; cursor:pointer;"
        >
            @foreach ($practices as $practice)
                <option value="{{ $practice->id }}" @selected($practice->id === $selectedId)>
                    {{ $practice->name }}
                </option>
            @endforeach
        </select>
    </form>
@else
    @php $name = auth()->user()?->practice?->name; @endphp
    @if ($name)
        <span style="font-size:0.8rem; color:#6b7280; margin-right:1rem; white-space:nowrap;">{{ $name }}</span>
    @endif
@endif
