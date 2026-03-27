<div>

{{-- ══════════════════════════════════════════════════════════════════════════
     CLOSED — practice has no active subscription
     ══════════════════════════════════════════════════════════════════════════ --}}
@if($this->closed)

  <div style="text-align:center;padding:3rem 1rem;">
    <div style="width:4rem;height:4rem;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1.25rem;">
      <span style="font-size:1.75rem;">&#x26A0;</span>
    </div>
    <h1 style="font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 0.75rem;">
      Online booking is currently unavailable
    </h1>
    <p style="color:#64748b;font-size:0.9375rem;line-height:1.6;max-width:28rem;margin:0 auto;">
      {{ $this->practice->name }} is not accepting new online bookings at this time.
      Please contact the practice directly to schedule an appointment.
    </p>
  </div>

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP PROGRESS BAR (steps 1–4 only)
     ══════════════════════════════════════════════════════════════════════════ --}}
@else

@if($step < 5)
  <div style="display:flex;gap:0.25rem;margin-bottom:1.75rem;">
    @foreach([1,2,3,4] as $s)
      <div style="flex:1;height:4px;border-radius:9999px;background:{{ $step >= $s ? '#0d9488' : '#e2e8f0' }};transition:background 0.2s;"></div>
    @endforeach
  </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 1 — Choose appointment type
     ══════════════════════════════════════════════════════════════════════════ --}}
@if($step === 1)

  <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.375rem;">
    Book an Appointment
  </h1>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    What would you like to book at <strong>{{ $this->practice->name }}</strong>?
  </p>

  @forelse($appointmentTypes as $type)
    <button wire:click="selectType({{ $type->id }})"
            style="display:block;width:100%;text-align:left;background:#ffffff;border:1.5px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;"
            onmouseover="this.style.borderColor='#0d9488';this.style.background='#f0fdfa'"
            onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#ffffff'">
      <p style="margin:0;font-weight:600;color:#0f172a;font-size:1rem;">{{ $type->name }}</p>
      <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.875rem;">
        {{ $type->duration_minutes ?? 60 }} min
        @if($type->defaultServiceFee)
          &nbsp;&middot;&nbsp; ${{ number_format($type->defaultServiceFee->default_price, 2) }}
        @endif
      </p>
    </button>
  @empty
    <p style="color:#94a3b8;font-size:0.9375rem;">
      No appointment types are available at this time. Please contact the practice directly.
    </p>
  @endforelse

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 2 — Choose practitioner
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 2)

  <button wire:click="backToStep1"
          style="background:none;border:none;color:#0d9488;font-size:0.875rem;cursor:pointer;padding:0;margin-bottom:1.25rem;display:inline-flex;align-items:center;gap:0.25rem;">
    &#8592; Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 0.375rem;">
    Choose a Practitioner
  </h2>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    <strong>{{ $selectedType?->name }}</strong> — who would you like to see?
  </p>

  <button wire:click="selectPractitioner(null)"
          style="display:block;width:100%;text-align:left;background:#ffffff;border:1.5px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;"
          onmouseover="this.style.borderColor='#0d9488';this.style.background='#f0fdfa'"
          onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#ffffff'">
    <p style="margin:0;font-weight:600;color:#0f172a;font-size:1rem;">No preference</p>
    <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.875rem;">We'll assign the next available practitioner</p>
  </button>

  @foreach($practitioners as $practitioner)
    <button wire:click="selectPractitioner({{ $practitioner->id }})"
            style="display:block;width:100%;text-align:left;background:#ffffff;border:1.5px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;margin-bottom:0.75rem;cursor:pointer;"
            onmouseover="this.style.borderColor='#0d9488';this.style.background='#f0fdfa'"
            onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#ffffff'">
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
    &#8592; Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 0.375rem;">
    Select a Date &amp; Time
  </h2>
  <p style="color:#64748b;font-size:0.9375rem;margin:0 0 1.5rem;">
    {{ $selectedType?->name }}
    @if($selectedPractitioner) &mdash; {{ $selectedPractitioner->user->name }} @endif
  </p>

  {{-- Month navigation --}}
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <button wire:click="prevMonth"
            style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 0.875rem;cursor:pointer;font-size:1rem;color:#475569;">&#8592;</button>
    <span style="font-weight:600;color:#0f172a;font-size:1rem;">{{ $monthLabel }}</span>
    <button wire:click="nextMonth"
            style="background:#f1f5f9;border:none;border-radius:0.5rem;padding:0.5rem 0.875rem;cursor:pointer;font-size:1rem;color:#475569;">&#8594;</button>
  </div>

  {{-- Day headers --}}
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
        <div style="text-align:center;padding:0.625rem 0;border-radius:0.5rem;color:#cbd5e1;font-size:0.875rem;cursor:default;user-select:none;">
          {{ $day['day'] }}
        </div>
      @else
        <button wire:click="selectDate('{{ $day['date'] }}')"
                style="text-align:center;padding:0.625rem 0;border-radius:0.5rem;border:2px solid {{ $selectedDate === $day['date'] ? '#0d9488' : 'transparent' }};background:{{ $selectedDate === $day['date'] ? '#ccfbf1' : '#f0fdf4' }};color:#0f766e;font-size:0.875rem;font-weight:600;cursor:pointer;">
          {{ $day['day'] }}
        </button>
      @endif
    @endforeach
  </div>

  {{-- Time slots --}}
  @if($selectedDate)
    <h3 style="font-size:0.9375rem;font-weight:600;color:#0f172a;margin:0 0 0.75rem;">
      Available times — {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}
    </h3>

    @if(count($availableSlots) > 0)
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(5rem,1fr));gap:0.5rem;margin-bottom:1.5rem;">
        @foreach($availableSlots as $slot)
          <button wire:click="selectSlot('{{ $slot }}')"
                  style="padding:0.5rem 0.25rem;border-radius:0.5rem;border:2px solid {{ $selectedSlot === $slot ? '#0d9488' : '#e2e8f0' }};background:{{ $selectedSlot === $slot ? '#0d9488' : '#ffffff' }};color:{{ $selectedSlot === $slot ? '#ffffff' : '#374151' }};font-size:0.8125rem;font-weight:500;cursor:pointer;text-align:center;">
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
    &#8592; Back
  </button>

  <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0 0 1rem;">Your Information</h2>

  {{-- Booking summary --}}
  <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:0.875rem;">
    <div style="display:grid;grid-template-columns:auto 1fr;gap:0.375rem 0.75rem;">
      <span style="color:#64748b;">Service</span>
      <span style="color:#0f172a;font-weight:600;">{{ $selectedType?->name }}</span>

      <span style="color:#64748b;">Practitioner</span>
      <span style="color:#0f172a;">{{ $selectedPractitioner ? $selectedPractitioner->user->name : 'Next available' }}</span>

      <span style="color:#64748b;">Date</span>
      <span style="color:#0f172a;font-weight:600;">{{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}</span>

      <span style="color:#64748b;">Time</span>
      <span style="color:#0f172a;font-weight:600;">{{ \Carbon\Carbon::createFromFormat('H:i', $selectedSlot)->format('g:i A') }}</span>
    </div>
  </div>

  <form wire:submit="confirmBooking">

    @if($errors->any())
      <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;padding:0.875rem 1rem;margin-bottom:1rem;color:#dc2626;font-size:0.875rem;">
        @foreach($errors->all() as $error)
          <p style="margin:0 0 0.25rem;">{{ $error }}</p>
        @endforeach
      </div>
    @endif

    {{-- Email (shown first so we can do the lookup on blur) --}}
    <div style="margin-bottom:1rem;">
      <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
        Email Address <span style="color:#dc2626;">*</span>
      </label>
      <input wire:model.lazy="patientEmail"
             type="email"
             placeholder="jane@example.com"
             autocomplete="email"
             style="display:block;width:100%;border:1.5px solid {{ $errors->has('patientEmail') ? '#fca5a5' : '#e2e8f0' }};border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;background:{{ $identityVerified ? '#f0fdfa' : '#fff' }};">
    </div>

    {{-- Identity verification block (shown when email matches an existing patient) --}}
    @if($existingPatientFound && !$identityVerified)
      <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:0.75rem;padding:1.25rem;margin-bottom:1rem;">
        @if($existingPatientHasPhone)
          <p style="margin:0 0 0.75rem;font-size:0.9375rem;color:#92400e;font-weight:500;">
            We found an account with this email address.
          </p>
          <p style="margin:0 0 0.875rem;font-size:0.875rem;color:#78350f;">
            To confirm it's you, please enter the <strong>last 4 digits</strong> of your phone number on file.
          </p>

          <div style="display:flex;gap:0.5rem;align-items:flex-start;">
            <input wire:model="verificationInput"
                   type="text"
                   inputmode="numeric"
                   maxlength="4"
                   placeholder="1234"
                   style="width:6rem;border:1.5px solid {{ $identityFailed ? '#fca5a5' : '#fde68a' }};border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:1.125rem;font-weight:700;letter-spacing:0.15em;color:#0f172a;outline:none;text-align:center;box-sizing:border-box;">
            <button wire:click.prevent="verifyIdentity"
                    style="background:#d97706;color:#ffffff;border:none;border-radius:0.5rem;padding:0.625rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;white-space:nowrap;">
              Verify
            </button>
          </div>

          @if($identityFailed)
            <p style="margin:0.5rem 0 0;color:#dc2626;font-size:0.8125rem;">
              Those digits don't match. Please try again or contact the practice to update your information.
            </p>
          @endif

          @if($errors->has('verificationInput'))
            <p style="margin:0.5rem 0 0;color:#dc2626;font-size:0.8125rem;">{{ $errors->first('verificationInput') }}</p>
          @endif
        @else
          {{-- Existing patient has no phone on file — can't verify --}}
          <p style="margin:0;font-size:0.9375rem;color:#92400e;">
            We found an account with this email but there is no phone number on file to verify against.
            Please contact <strong>{{ $this->practice->name }}</strong> to update your record, or use a different email.
          </p>
        @endif
      </div>
    @endif

    @if($existingPatientFound && $identityVerified)
      <div style="background:#f0fdfa;border:1.5px solid #99f6e4;border-radius:0.5rem;padding:0.625rem 0.875rem;margin-bottom:1rem;font-size:0.875rem;color:#0f766e;">
        &#10003;&nbsp; Identity verified — your details have been pre-filled.
      </div>
    @endif

    {{-- Name fields (hidden until email verified for existing patients) --}}
    @if(!$existingPatientFound || $identityVerified)

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
        <div>
          <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
            First Name <span style="color:#dc2626;">*</span>
          </label>
          <input wire:model="patientFirstName"
                 type="text"
                 placeholder="Jane"
                 autocomplete="given-name"
                 style="display:block;width:100%;border:1.5px solid {{ $errors->has('patientFirstName') ? '#fca5a5' : '#e2e8f0' }};border-radius:0.5rem;padding:0.625rem 0.75rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
            Last Name <span style="color:#dc2626;">*</span>
          </label>
          <input wire:model="patientLastName"
                 type="text"
                 placeholder="Smith"
                 autocomplete="family-name"
                 style="display:block;width:100%;border:1.5px solid {{ $errors->has('patientLastName') ? '#fca5a5' : '#e2e8f0' }};border-radius:0.5rem;padding:0.625rem 0.75rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
        </div>
      </div>

      <div style="margin-bottom:1.5rem;">
        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">
          Phone Number <span style="color:#94a3b8;font-size:0.8125rem;">(optional)</span>
        </label>
        <input wire:model="patientPhone"
               type="tel"
               placeholder="(555) 555-5555"
               autocomplete="tel"
               style="display:block;width:100%;border:1.5px solid #e2e8f0;border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.9375rem;color:#0f172a;outline:none;box-sizing:border-box;">
      </div>

      <button type="submit"
              wire:loading.attr="disabled"
              style="display:block;width:100%;background:#0d9488;color:#ffffff;border:none;border-radius:0.5rem;padding:0.875rem 1.25rem;font-size:1rem;font-weight:600;cursor:pointer;">
        <span wire:loading.remove>Confirm Appointment</span>
        <span wire:loading>Booking&#8230;</span>
      </button>

    @endif

  </form>

{{-- ══════════════════════════════════════════════════════════════════════════
     STEP 5 — Confirmation
     ══════════════════════════════════════════════════════════════════════════ --}}
@elseif($step === 5)

  <div style="text-align:center;padding:2rem 0 1.25rem;">
    <div style="width:4.5rem;height:4.5rem;background:#ccfbf1;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1rem;">
      <span style="font-size:2rem;color:#0d9488;">&#10003;</span>
    </div>
    <h2 style="font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">You&rsquo;re booked!</h2>
    <p style="color:#64748b;font-size:0.9375rem;margin:0 0 0.25rem;">
      A confirmation email has been sent to <strong>{{ $patientEmail }}</strong>.
    </p>
    <p style="color:#94a3b8;font-size:0.875rem;margin:0 0 2rem;">
      Please complete the forms below before your visit.
    </p>
  </div>

  <div style="margin-bottom:0.75rem;">
    <a href="{{ $intakeUrl }}"
       style="display:block;background:#0d9488;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;text-align:center;">
      &#128203; Complete Intake Form
    </a>
  </div>

  <div style="margin-bottom:1.5rem;">
    <a href="{{ $consentUrl }}"
       style="display:block;background:#0f766e;color:#ffffff;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.9375rem;text-align:center;">
      &#9997; Sign Consent Form
    </a>
  </div>

  @if($googleCalendarUrl)
    <div style="margin-bottom:2rem;">
      <a href="{{ $googleCalendarUrl }}" target="_blank" rel="noopener"
         style="display:block;background:#f1f5f9;color:#374151;text-decoration:none;padding:0.875rem 1.25rem;border-radius:0.5rem;font-weight:500;font-size:0.9375rem;text-align:center;border:1.5px solid #e2e8f0;">
        &#128197; Add to Google Calendar
      </a>
    </div>
  @endif

  <p style="color:#94a3b8;font-size:0.8125rem;text-align:center;line-height:1.6;">
    If you need to reschedule or cancel, please contact {{ $this->practice->name }} directly.
  </p>

@endif

@endif {{-- /closed --}}

</div>
