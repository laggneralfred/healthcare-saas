<?php

namespace App\Livewire\Public;

use App\Jobs\SendBookingEmails;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\ConsentRecord;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Component;

class BookingCalendar extends Component
{
    // ── Practice ───────────────────────────────────────────────────────────────
    public Practice $practice;

    /** Set to true when the practice has no active subscription. */
    public bool $closed = false;

    // ── Wizard step (1–5) ──────────────────────────────────────────────────────
    public int $step = 1;

    // ── Step 1: Appointment type ───────────────────────────────────────────────
    public ?int $selectedTypeId = null;

    // ── Step 2: Practitioner ───────────────────────────────────────────────────
    public ?int $selectedPractitionerId = null;   // null = "any"

    // ── Step 3: Date / time slot ───────────────────────────────────────────────
    public int  $calendarYear;
    public int  $calendarMonth;
    public ?string $selectedDate = null;   // Y-m-d in practice timezone
    public ?string $selectedSlot = null;   // H:i in practice timezone

    // ── Step 4: Patient info ───────────────────────────────────────────────────
    public string $patientFirstName = '';
    public string $patientLastName  = '';
    public string $patientEmail     = '';
    public string $patientPhone     = '';

    // Existing-patient identity verification
    public bool    $existingPatientFound  = false;
    public ?int    $existingPatientId     = null;
    public bool    $existingPatientHasPhone = false;
    public string  $verificationInput     = '';   // last 4 digits entered by user
    public bool    $identityVerified      = false;
    public bool    $identityFailed        = false;

    // ── Step 5: Confirmation data ──────────────────────────────────────────────
    public ?int    $bookedAppointmentId = null;
    public ?string $intakeUrl           = null;
    public ?string $consentUrl          = null;
    public ?string $googleCalendarUrl   = null;

    // ── Mount ──────────────────────────────────────────────────────────────────

    public function mount(Practice $practice): void
    {
        $this->practice = $practice;

        // Subscription gate — block bookings on practices without an active plan.
        // Bypass in local/testing so demo works without Stripe.
        if (! app()->isLocal() && ! app()->environment('testing')) {
            if (! $practice->is_active || ! $practice->subscribed('default')) {
                $this->closed = true;
                return;
            }
        }

        $tz = $practice->timezone ?? 'UTC';
        $now = Carbon::now($tz);
        $this->calendarYear  = $now->year;
        $this->calendarMonth = $now->month;
    }

    // ── Step 1 ─────────────────────────────────────────────────────────────────

    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
        $this->step = 2;
    }

    // ── Step 2 ─────────────────────────────────────────────────────────────────

    public function selectPractitioner(?int $practitionerId): void
    {
        $this->selectedPractitionerId = $practitionerId;
        $this->step = 3;
    }

    public function backToStep1(): void
    {
        $this->selectedTypeId = null;
        $this->step = 1;
    }

    // ── Step 3 ─────────────────────────────────────────────────────────────────

    public function prevMonth(): void
    {
        $date = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->subMonth();
        $this->calendarYear  = $date->year;
        $this->calendarMonth = $date->month;
        $this->selectedDate  = null;
        $this->selectedSlot  = null;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->addMonth();
        $this->calendarYear  = $date->year;
        $this->calendarMonth = $date->month;
        $this->selectedDate  = null;
        $this->selectedSlot  = null;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = null;
    }

    public function selectSlot(string $slot): void
    {
        $this->selectedSlot = $slot;
    }

    public function confirmSlot(): void
    {
        if (! $this->selectedDate || ! $this->selectedSlot) {
            return;
        }
        $this->step = 4;
    }

    public function backToStep2(): void
    {
        $this->selectedDate = null;
        $this->selectedSlot = null;
        $this->step = 2;
    }

    // ── Step 4: identity verification ─────────────────────────────────────────

    /**
     * Fires when the email field loses focus (wire:model.lazy).
     * Checks if an existing patient has this email so we can ask for
     * phone verification before pre-filling their details.
     */
    public function updatedPatientEmail(string $value): void
    {
        // Reset verification state whenever the email changes.
        $this->existingPatientFound   = false;
        $this->existingPatientId      = null;
        $this->existingPatientHasPhone = false;
        $this->verificationInput      = '';
        $this->identityVerified       = false;
        $this->identityFailed         = false;

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $existing = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', strtolower(trim($value)))
            ->first();

        if ($existing) {
            $this->existingPatientFound    = true;
            $this->existingPatientId       = $existing->id;
            $this->existingPatientHasPhone = filled($existing->phone);
        }
    }

    /**
     * Verify the patient's identity using the last 4 digits of their phone.
     * On success, pre-fills name and phone from the existing patient record.
     */
    public function verifyIdentity(): void
    {
        $this->identityFailed = false;

        $this->validate(['verificationInput' => 'required|digits:4']);

        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->findOrFail($this->existingPatientId);

        $stored = preg_replace('/\D/', '', $patient->phone ?? '');
        $entered = trim($this->verificationInput);

        if ($stored === '' || ! str_ends_with($stored, $entered)) {
            $this->identityFailed = true;
            return;
        }

        // Pre-fill from the existing record
        $parts = explode(' ', $patient->name, 2);
        $this->patientFirstName = $parts[0] ?? $patient->name;
        $this->patientLastName  = $parts[1] ?? '';
        $this->patientPhone     = $patient->phone ?? '';
        $this->identityVerified = true;
    }

    // ── Step 4: book ───────────────────────────────────────────────────────────

    public function confirmBooking(): void
    {
        // If the email matched an existing patient, identity must be verified first.
        if ($this->existingPatientFound && ! $this->identityVerified) {
            $this->addError('patientEmail', 'Please verify your identity before continuing.');
            return;
        }

        $this->validate([
            'patientFirstName' => 'required|string|max:255',
            'patientLastName'  => 'required|string|max:255',
            'patientEmail'     => 'required|email|max:255',
            'patientPhone'     => 'nullable|string|max:50',
        ]);

        $tz   = $this->practice->timezone ?? 'UTC';
        $type = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->findOrFail($this->selectedTypeId);

        // Determine practitioner
        if ($this->selectedPractitionerId) {
            $practitioner = Practitioner::withoutPracticeScope()
                ->where('practice_id', $this->practice->id)
                ->findOrFail($this->selectedPractitionerId);
        } else {
            $practitioner = $this->pickAvailablePractitioner();
            if (! $practitioner) {
                $this->addError('patientFirstName', 'No practitioners are available for this slot. Please choose another time.');
                return;
            }
        }

        // Build start / end datetimes in UTC
        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->selectedSlot}", $tz);
        $startUtc   = $startLocal->copy()->utc();
        $endUtc     = $startUtc->copy()->addMinutes($type->duration_minutes ?? 60);

        $fullName = trim("{$this->patientFirstName} {$this->patientLastName}");

        // Upsert patient — match by email within practice
        if ($this->existingPatientId) {
            $patient = Patient::withoutPracticeScope()->findOrFail($this->existingPatientId);
            $patient->update(array_filter([
                'name'  => $fullName,
                'phone' => $this->patientPhone ?: null,
            ]));
        } else {
            $patient = Patient::withoutPracticeScope()->firstOrCreate(
                ['practice_id' => $this->practice->id, 'email' => strtolower(trim($this->patientEmail))],
                ['name' => $fullName, 'phone' => $this->patientPhone ?: null, 'is_patient' => true]
            );
        }

        // Create appointment
        $appointment = Appointment::withoutPracticeScope()->create([
            'practice_id'         => $this->practice->id,
            'patient_id'          => $patient->id,
            'practitioner_id'     => $practitioner->id,
            'appointment_type_id' => $type->id,
            'status'              => 'scheduled',
            'start_datetime'      => $startUtc,
            'end_datetime'        => $endUtc,
        ]);

        // Auto-create intake + consent tokens
        $intake = MedicalHistory::withoutPracticeScope()->create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $appointment->id,
            'status'         => 'pending',
        ]);

        $consent = ConsentRecord::withoutPracticeScope()->create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $appointment->id,
            'status'         => 'pending',
        ]);

        // Dispatch confirmation emails
        SendBookingEmails::dispatch($appointment, $intake, $consent);

        // Google Calendar link
        $gcStart = $startUtc->format('Ymd\THis\Z');
        $gcEnd   = $endUtc->format('Ymd\THis\Z');
        $gcTitle = urlencode($type->name . ' at ' . $this->practice->name);
        $this->googleCalendarUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$gcTitle}&dates={$gcStart}/{$gcEnd}";

        $this->intakeUrl           = $intake->getPublicUrl();
        $this->consentUrl          = $consent->getPublicUrl();
        $this->bookedAppointmentId = $appointment->id;
        $this->step = 5;
    }

    public function backToStep3(): void
    {
        $this->step = 3;
    }

    // ── Computed helpers ──────────────────────────────────────────────────────

    public function getAvailablePractitioners(): Collection
    {
        return Practitioner::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('is_active', true)
            ->with('user')
            ->get();
    }

    public function getCalendarDays(): array
    {
        $tz       = $this->practice->timezone ?? 'UTC';
        $today    = Carbon::now($tz)->startOfDay();
        $firstDay = Carbon::create($this->calendarYear, $this->calendarMonth, 1, 0, 0, 0, $tz);
        $lastDay  = $firstDay->copy()->endOfMonth();

        // Pre-load all appointments for the month in one query.
        $monthBookings = $this->monthlyBookings();

        $startOffset = $firstDay->dayOfWeek;  // 0 = Sunday
        $days = [];

        for ($i = 0; $i < $startOffset; $i++) {
            $days[] = null;
        }

        for ($d = 1; $d <= $lastDay->day; $d++) {
            $date = Carbon::create($this->calendarYear, $this->calendarMonth, $d, 0, 0, 0, $tz);
            $ymd  = $date->format('Y-m-d');

            $isPast    = $date->lt($today);
            $isWeekend = $date->isWeekend();
            $hasSlots  = ! $isPast && ! $isWeekend && $this->dateHasAvailableSlots($ymd, $monthBookings);

            $days[] = [
                'day'       => $d,
                'date'      => $ymd,
                'isPast'    => $isPast,
                'isWeekend' => $isWeekend,
                'hasSlots'  => $hasSlots,
            ];
        }

        return $days;
    }

    public function getAvailableSlots(): array
    {
        if (! $this->selectedDate) {
            return [];
        }

        return $this->buildSlotsForDay($this->selectedDate, $this->monthlyBookings());
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Fetch all non-closed appointments for the currently displayed month.
     * Called once per render; callers pass the result around to avoid N+1 queries.
     */
    private function monthlyBookings(): Collection
    {
        $tz         = $this->practice->timezone ?? 'UTC';
        $monthStart = Carbon::create($this->calendarYear, $this->calendarMonth, 1, 0, 0, 0, $tz)->utc();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $q = Appointment::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->whereBetween('start_datetime', [$monthStart, $monthEnd])
            ->whereNotIn('status', ['closed']);

        if ($this->selectedPractitionerId) {
            $q->where('practitioner_id', $this->selectedPractitionerId);
        }

        return $q->get(['start_datetime', 'end_datetime']);
    }

    private function dateHasAvailableSlots(string $ymd, Collection $monthBookings): bool
    {
        return count($this->buildSlotsForDay($ymd, $monthBookings)) > 0;
    }

    /**
     * Return all unbooked 30-min slot strings (H:i) for a given day.
     *
     * Slots run 09:00–16:30 in 30-minute increments.
     * A slot is blocked when any existing appointment overlaps it
     * (overlap = appt_start < slot_end AND appt_end > slot_start).
     */
    private function buildSlotsForDay(string $ymd, Collection $monthBookings): array
    {
        $tz = $this->practice->timezone ?? 'UTC';

        // Filter down to just this day's bookings (already UTC in the collection)
        $dayStart = Carbon::createFromFormat('Y-m-d', $ymd, $tz)->startOfDay()->utc();
        $dayEnd   = $dayStart->copy()->endOfDay();
        $dayBookings = $monthBookings->filter(
            fn ($a) => $a->start_datetime >= $dayStart && $a->start_datetime <= $dayEnd
        );

        $available = [];

        for ($h = 9; $h < 17; $h++) {
            foreach ([0, 30] as $m) {
                $slot      = sprintf('%02d:%02d', $h, $m);
                $slotStart = Carbon::createFromFormat('Y-m-d H:i', "$ymd $slot", $tz)->utc();
                $slotEnd   = $slotStart->copy()->addMinutes(30);

                $blocked = $dayBookings->contains(
                    fn ($a) => $a->start_datetime < $slotEnd && $a->end_datetime > $slotStart
                );

                if (! $blocked) {
                    $available[] = $slot;
                }
            }
        }

        return $available;
    }

    private function pickAvailablePractitioner(): ?Practitioner
    {
        $tz         = $this->practice->timezone ?? 'UTC';
        $slotStart  = Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->selectedSlot}", $tz)->utc();
        $slotEnd    = $slotStart->copy()->addMinutes(30);

        $busyIds = Appointment::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('start_datetime', '<', $slotEnd)
            ->where('end_datetime', '>', $slotStart)
            ->whereNotIn('status', ['closed'])
            ->pluck('practitioner_id');

        return Practitioner::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('is_active', true)
            ->whereNotIn('id', $busyIds)
            ->first();
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $appointmentTypes = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('is_active', true)
            ->with('defaultServiceFee')
            ->get();

        $practitioners = $this->getAvailablePractitioners();

        $selectedType = $this->selectedTypeId
            ? $appointmentTypes->firstWhere('id', $this->selectedTypeId)
            : null;

        $selectedPractitioner = $this->selectedPractitionerId
            ? $practitioners->firstWhere('id', $this->selectedPractitionerId)
            : null;

        $calendarDays   = $this->step === 3 ? $this->getCalendarDays() : [];
        $availableSlots = $this->step === 3 ? $this->getAvailableSlots() : [];
        $monthLabel     = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->format('F Y');

        return view('livewire.public.booking-calendar', compact(
            'appointmentTypes',
            'practitioners',
            'selectedType',
            'selectedPractitioner',
            'calendarDays',
            'availableSlots',
            'monthLabel',
        ))->layout('layouts.booking', ['practiceNameHeader' => $this->practice->name]);
    }
}
