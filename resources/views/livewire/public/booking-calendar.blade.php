<div>

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 1 — Choose appointment type
     ══════════════════════════════════════════════════════════════════════════ --}}
@if($step === 1)

  <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">
    Book an Appointment
  </h1>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    Select the type of appointment you'd like to book with {{ $this->practice->name }}.
  </p>

  @forelse($appointmentTypes as $type)
    <button wire:click="selectType({{ $type->id }})"
            style="display:block;width:100%;text-align:left;background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;transition:border-color 0.15s;"
            onmouseover="this.style.borderColor='#0d9488'" onmouseout="this.style.borderColor='#e2e8f0'">
      <p style="margin:0;font-weight:600;color:#0f172a;font-size:1rem;">{{ $type->name }}</p>
      @if($type->defaultServiceFee)
        <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.875rem;">
          ${{ number_format($type->defaultServiceFee->amount, 2) }}
        </p>
      @endif
    </button>
  @empty
    <p style="color:#94a3b8;font-size:0.9375rem;">No appointment types are available. Please contact the practice.</p>
  @endforelse

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 2 — Choose practitioner
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 2)

  <button wire:click="backToStep1"
          style="background:none;border:none;color:#0d9488;font-size:0.875rem;cursor:pointer;padding:0;margin-bottom:1.25rem;">
    ← Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">
    Choose a Practitioner
  </h2>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    <strong>{{ $selectedType?->name }}</strong> — who would you like to see?
  </p>

  {{-- Any available --}}
  <button wire:click="selectPractitioner(null)"
          style="display:block;width:100%;text-align:left;background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;"
          onmouseover="this.style.borderColor='#0d9488'" onmouseout="this.style.borderColor='#e2e8f0'">
    <p style="margin:0;font-weight:600;color:#0f172a;font-size:1rem;">Any available practitioner</p>
    <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.875rem;">We'll assign the next available practitioner</p>
  </button>

  @foreach($practitioners as $practitioner)
    <button wire:click="selectPractitioner({{ $practitioner->id }})"
            style="display:block;width:100%;text-align:left;background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;"
            onmouseover="this.style.borderColor='#0d9488'" onmouseout="this.style.borderColor='#e2e8f0'">
      <p style="margin:0;font-weight:600;color:#0f172a;font-size:1rem;">{{ $practitioner->user->name }}</p>
      @if($practitioner->specialty)
        <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.875rem;">{{ $practitioner->specialty }}</p>
      @endif
    </button>
  @endforeach

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 3 — Pick a date and time
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 3)

  <button wire:click="backToStep2"
          style="background:none;border:none;color:#0d9488;font-size:0.875rem;cursor:pointer;padding:0;margin-bottom:1.25rem;">
    ← Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">
    Select a Date &amp; Time
  </h2>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    {{ $selectedType?->name }}
    @if($selectedPractitioner)
      &mdash; {{ $selectedPractitioner->user->name }}
    @endif
  </p>

  {{-- Month navigation --}}
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <button wire:click="prevMonth" style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 0.75rem;cursor:pointer;font-size:1rem;color:#475569;">&#8592;</button>
    <span style="font-weight:600;color:#0f172a;font-size:1rem;">{{ $monthLabel }}</span>
    <button wire:click="nextMonth" style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 0.75rem;cursor:pointer;font-size:1rem;color:#475569;">&#8594;</button>
  </div>

  {{-- Day-of-week headers --}}
  <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:0.25rem;margin-bottom:0.25rem;">
    @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $dow)
      <div style="text-align:center;font-size:0.75rem;font-weight:600;color:#94a3b8;padding:0.25rem 0;">{{ $dow }}</div>
    @endforeach
  </div>

  {{-- Calendar grid --}}
  <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:0.25rem;margin-bottom:1.5rem;">
    @foreach($calendarDays as $day)
      @if($day === null)
        <div></div>
      @elseif($day['isPast'] || $day['isWeekend'] || !$day['hasSlots'])
        <div style="text-align:center;padding:0.5rem 0;border-radius:0.5rem;color:#cbd5e1;font-size:0.875rem;cursor:default;">
          {{ $day['day'] }}
        </div>
      @else
        <button wire:click="selectDate('{{ $day['date'] }}')"
                style="text-align:center;padding:0.5rem 0;border-radius:0.5rem;border:2px solid {{ $selectedDate === $day['date'] ? '#0d9488' : 'transparent' }};background:{{ $selectedDate === $day['date'] ? '#ccfbf1' : '#f0fdf4' }};color:#0d9488;font-size:0.875rem;font-weight:600;cursor:pointer;">
          {{ $day['day'] }}
        </button>
      @endif
    @endforeach
  </div>

  {{-- Time slots --}}
  @if($selectedDate)
    <h3 style="font-size:1rem;font-weight:600;color:#0f172a;margin:0 0 0.75rem;">
      Available times on {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}
    </h3>

    @if(count($availableSlots) > 0)
      <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem;">
        @foreach($availableSlots as $slot)
          <button wire:click="selectSlot('{{ $slot }}')"
                  style="padding:0.5rem 1rem;border-radius:0.5rem;border:2px solid {{ $selectedSlot === $slot ? '#0d9488' : '#e2e8f0' }};background:{{ $selectedSlot === $slot ? '#0d9488' : '#ffffff' }};color:{{ $selectedSlot === $slot ? '#ffffff' : '#374151' }};font-size:0.875rem;font-weight:500;cursor:pointer;">
            {{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format('g:i A') }}
          </button>
        @endforeach
      </div>

      @if($selectedSlot)
        <button wire:click="confirmSlot"
                style="display:block;width:100%;background:#0d9488;color:#ffffff;border:none;border-radius:0.5rem;padding:0.875rem 1.25rem;font-size:1rem;font-weight:600;cursor:pointer;">
          Continue with {{ \Carbon\Carbon::createFromFormat('H:i', $selectedSlot)->format('g:i A') }}
        </button>
      @endif
    @else
      <p style="color:#94a3b8;font-size:0.9375rem;">No available times on this date. Please select another day.</p>
    @endif
  @endif

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 4 — Patient information
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 4)

  <button wire:click="backToStep3"
          style="background:none;border:none;color:#0d9488;font-size:0.875rem;cursor:pointer;padding:0;margin-bottom:1.25rem;">
    ← Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">
    Your Information
  </h2>

  {{-- Summary card --}}
  <div style="background:#f1f5f9;border-radius:0.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <table cellpadding="4" cellspacing="0" width="100%">
      <tr>
        <td style="color:#64748b;font-size:0.875rem;width:40%;">Type</td>
        <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">{{ $selectedType?->name }}</td>
      </tr>
      <tr>
        <td style="color:#64748b;font-size:0.875rem;">Practitioner</td>
        <td style="color:#0f172a;font-size:0.875rem;">
          {{ $selectedPractitioner ? $selectedPractitioner->user->name : 'Next available' }}
        </td>
      </tr>
      <tr>
        <td style="color:#64748b;font-size:0.875rem;">Date</td>
        <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">
          {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
        </td>
      </tr>
      <tr>
        <td style="color:#64748b;font-size:0.875rem;">Time</td>
        <td style="color:#0f172a;font-size:0.875rem;font-weight:600;">
          {{ \Carbon\Carbon::createFromFormat('H:i', $selectedSlot)->format('g:i A') }}
        </td>
      </tr>
    </table>
  </div>

  <form wire:submit="confirmBooking">

    @if($errors->any())
      <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;padding:0.875rem 1rem;margin-bottom:1rem;color:#dc2626;font-size:0.875rem;">
        @foreach($errors->all() as $error)
          <p style="margin:0 0 0.25rem;">{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <div style="margin-bottom:1rem;">
      <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
        Full Name <span style="color:#dc2626;">*</span>
      </label>
      <input wire:model="patientName"
             type="text"
             placeholder="Jane Smith"
             style="display:block;width:100%;border:1px solid {{ $errors->has('patientName') ? '#fca5a5' : '#e2e8f0' }};border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:1rem;">
      <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
        Email Address <span style="color:#dc2626;">*</span>
      </label>
      <input wire:model="patientEmail"
             type="email"
             placeholder="jane@example.com"
             style="display:block;width:100%;border:1px solid {{ $errors->has('patientEmail') ? '#fca5a5' : '#e2e8f0' }};border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:1.5rem;">
      <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
        Phone Number <span style="color:#94a3b8;">(optional)</span>
      </label>
      <input wire:model="patientPhone"
             type="tel"
             placeholder="(555) 555-5555"
             style="display:block;width:100%;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
    </div>

    <button type="submit"
            wire:loading.attr="disabled"
            style="display:block;width:100%;background:#0d9488;color:#ffffff;border:none;border-radius:0.5rem;padding:0.875rem 1.25rem;font-size:1rem;font-weight:600;cursor:pointer;">
      <span wire:loading.remove>Confirm Appointment</span>
      <span wire:loading>Booking...</span>
    </button>

  </form>

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 5 — Confirmation
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 5)

  <div style="text-align:center;padding:2rem 0 1rem;">
    <div style="width:4rem;height:4rem;background:#ccfbf1;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1rem;">
      <span style="font-size:1.75rem;">&#10003;</span>
    </div>
    <h2 style="font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">You're booked!</h2>
    <p style="color:#64748b;font-size:0.9375rem;margin:0 0 0.25rem;">
      A confirmation email has been sent to <strong>{{ $patientEmail }}</strong>.
    </p>
    <p style="color:#94a3b8;font-size:0.875rem;margin:0 0 2rem;">
      Please complete the forms below before your visit.
    </p>
  </div>

  {{-- Action cards --}}
  <div style="margin-bottom:0.75rem;">
    <a href="{{ $intakeUrl }}"
       style="display:block;background:#0d9488;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;text-align:center;">
      📋 Complete Intake Form
    </a>
  </div>

  <div style="margin-bottom:1.5rem;">
    <a href="{{ $consentUrl }}"
       style="display:block;background:#0f766e;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;text-align:center;">
      ✍️ Sign Consent Form
    </a>
  </div>

  @if($googleCalendarUrl)
    <div style="margin-bottom:2rem;">
      <a href="{{ $googleCalendarUrl }}" target="_blank"
         style="display:block;background:#f1f5f9;color:#374151;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:500;font-size:0.9375rem;text-align:center;border:1px solid #e2e8f0;">
        📅 Add to Google Calendar
      </a>
    </div>
  @endif

  <p style="color:#94a3b8;font-size:0.8125rem;text-align:center;line-height:1.5;">
    If you need to reschedule or cancel, please contact {{ $this->practice->name }} directly.
  </p>

@endif

</div>
