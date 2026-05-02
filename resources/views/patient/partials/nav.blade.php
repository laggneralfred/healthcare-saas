<nav class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
    <a href="{{ route('patient.dashboard') }}" class="font-semibold text-teal-700 hover:text-teal-900">
        {{ $practice->name ?? 'Practiq' }}
    </a>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('patient.dashboard') }}" class="font-semibold text-slate-600 hover:text-slate-900">Dashboard</a>
        <a href="{{ route('patient.forms.index') }}" class="font-semibold text-slate-600 hover:text-slate-900">Forms</a>
        <a href="{{ route('patient.appointment-request.create') }}" class="font-semibold text-slate-600 hover:text-slate-900">Request appointment</a>
        <form method="POST" action="{{ route('patient.logout') }}">
            @csrf
            <button type="submit" class="font-semibold text-slate-500 hover:text-slate-900">Log out</button>
        </form>
    </div>
</nav>
