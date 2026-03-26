<?php

namespace App\Livewire\Public;

use App\Jobs\SendBookingEmails;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\ConsentRecord;
use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.booking')]
class BookingCalendar extends Component
{
    public Practice $practice;

    // ── Wizard step (1–5) ──────────────────────────────────────────────────────
    public int $step = 1;

    // ── Step 1: Appointment type ───────────────────────────────────────────────
    public ?int $selectedTypeId = null;

    // ── Step 2: Practitioner ───────────────────────────────────────────────────
    public ?int $selectedPractitionerId = null; // null = "any"

    // ── Step 3: Date / time slot ───────────────────────────────────────────────
    public int $calendarYear;
    public int $calendarMonth;
    public ?string $selectedDate = null;     // Y-m-d
    public ?string $selectedSlot = null;     // H:i (practice-timezone local time)

    // ── Step 4: Patient info ───────────────────────────────────────────────────
    #[Validate('required|string|max:255')]
    public string $patientName = '';

    #[Validate('required|email|max:255')]
    public string $patientEmail = '';

    #[Validate('nullable|string|max:50')]
    public string $patientPhone = '';

    // ── Step 5: Confirmation data ──────────────────────────────────────────────
    public ?int $bookedAppointmentId = null;
    public ?string $intakeUrl = null;
    public ?string $consentUrl = null;
    public ?string $googleCalendarUrl = null;

    // ── Mount ──────────────────────────────────────────────────────────────────

    public function mount(Practice $practice): void
    {
        $this->practice = $practice;

        $tz = $this->practice->timezone ?? 'UTC';
        $now = Carbon::now($tz);

        $this->calendarYear  = $now->year;
        $this->calendarMonth = $now->month;
    }

    // ── Step 1 actions ────────────────────────────────────────────────────────

    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
        $this->step = 2;
    }

    // ── Step 2 actions ────────────────────────────────────────────────────────

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

    // ── Step 3 actions ────────────────────────────────────────────────────────

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
        if (!$this->selectedDate || !$this->selectedSlot) {
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

    // ── Step 4 actions ────────────────────────────────────────────────────────

    public function confirmBooking(): void
    {
        $this->validate();

        $tz   = $this->practice->timezone ?? 'UTC';
        $type = AppointmentType::findOrFail($this->selectedTypeId);

        // Determine practitioner
        if ($this->selectedPractitionerId) {
            $practitioner = Practitioner::findOrFail($this->selectedPractitionerId);
        } else {
            // Pick any available practitioner for the slot
            $practitioner = $this->getAvailablePractitionerForSlot();
            if (!$practitioner) {
                $this->addError('patientName', 'No practitioners are available for this slot. Please choose another time.');
                return;
            }
        }

        // Build start/end datetimes in UTC
        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->selectedSlot}", $tz);
        $startUtc   = $startLocal->copy()->utc();
        $endUtc     = $startUtc->copy()->addHour();

        // Upsert patient (match by email within practice)
        $patient = Patient::firstOrCreate(
            ['practice_id' => $this->practice->id, 'email' => $this->patientEmail],
            ['name' => $this->patientName, 'phone' => $this->patientPhone ?: null, 'is_patient' => true],
        );

        // If name/phone changed, update them
        $patient->update(array_filter([
            'name'  => $this->patientName,
            'phone' => $this->patientPhone ?: null,
        ]));

        // Create appointment
        $appointment = Appointment::create([
            'practice_id'         => $this->practice->id,
            'patient_id'          => $patient->id,
            'practitioner_id'     => $practitioner->id,
            'appointment_type_id' => $type->id,
            'status'              => 'scheduled',
            'start_datetime'      => $startUtc,
            'end_datetime'        => $endUtc,
        ]);

        // Auto-create intake + consent (tokens generated by HasAccessToken boot)
        $intake = IntakeSubmission::create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $appointment->id,
            'status'         => 'pending',
        ]);

        $consent = ConsentRecord::create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $appointment->id,
            'status'         => 'pending',
        ]);

        // Dispatch emails via queue
        SendBookingEmails::dispatch($appointment, $intake, $consent);

        // Build Google Calendar link
        $gcStart = $startUtc->format('Ymd\THis\Z');
        $gcEnd   = $endUtc->format('Ymd\THis\Z');
        $gcTitle = urlencode($type->name . ' at ' . $this->practice->name);
        $this->googleCalendarUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$gcTitle}&dates={$gcStart}/{$gcEnd}";

        $this->intakeUrl  = $intake->getPublicUrl();
        $this->consentUrl = $consent->getPublicUrl();
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
        return $this->practice->practitioners()
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

        // Days in week before the 1st (0=Sun offset)
        $startOffset = $firstDay->dayOfWeek; // 0=Sun, 1=Mon ...

        $days = [];

        // Empty leading cells
        for ($i = 0; $i < $startOffset; $i++) {
            $days[] = null;
        }

        for ($d = 1; $d <= $lastDay->day; $d++) {
            $date = Carbon::create($this->calendarYear, $this->calendarMonth, $d, 0, 0, 0, $tz);
            $ymd  = $date->format('Y-m-d');

            $isPast    = $date->lt($today);
            $isWeekend = $date->isWeekend();
            $hasSlots  = !$isPast && !$isWeekend && $this->dateHasAvailableSlots($ymd);

            $days[] = [
                'day'      => $d,
                'date'     => $ymd,
                'isPast'   => $isPast,
                'isWeekend'=> $isWeekend,
                'hasSlots' => $hasSlots,
            ];
        }

        return $days;
    }

    public function getAvailableSlots(): array
    {
        if (!$this->selectedDate) {
            return [];
        }
        return $this->buildAvailableSlots($this->selectedDate);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function dateHasAvailableSlots(string $ymd): bool
    {
        return count($this->buildAvailableSlots($ymd)) > 0;
    }

    private function buildAvailableSlots(string $ymd): array
    {
        $tz = $this->practice->timezone ?? 'UTC';

        // Generate 9am–5pm in 1-hour increments
        $slots = [];
        for ($h = 9; $h < 17; $h++) {
            $slots[] = sprintf('%02d:00', $h);
        }

        // Fetch booked start times for the given date
        $bookedTimes = $this->getBookedTimesForDate($ymd);

        return array_values(array_filter($slots, fn($slot) => !in_array($slot, $bookedTimes)));
    }

    private function getBookedTimesForDate(string $ymd): array
    {
        $tz = $this->practice->timezone ?? 'UTC';

        $dayStart = Carbon::createFromFormat('Y-m-d', $ymd, $tz)->startOfDay()->utc();
        $dayEnd   = $dayStart->copy()->endOfDay();

        $query = Appointment::where('practice_id', $this->practice->id)
            ->whereBetween('start_datetime', [$dayStart, $dayEnd])
            ->whereNotIn('status', ['closed']);

        if ($this->selectedPractitionerId) {
            $query->where('practitioner_id', $this->selectedPractitionerId);
        }

        return $query->get()
            ->map(fn($a) => $a->start_datetime->setTimezone($tz)->format('H:i'))
            ->toArray();
    }

    private function getAvailablePractitionerForSlot(): ?Practitioner
    {
        $tz = $this->practice->timezone ?? 'UTC';
        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->selectedSlot}", $tz);
        $startUtc   = $startLocal->utc();
        $endUtc     = $startUtc->copy()->addHour();

        $busyIds = Appointment::where('practice_id', $this->practice->id)
            ->where('start_datetime', $startUtc)
            ->whereNotIn('status', ['closed'])
            ->pluck('practitioner_id');

        return Practitioner::where('practice_id', $this->practice->id)
            ->where('is_active', true)
            ->whereNotIn('id', $busyIds)
            ->first();
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $appointmentTypes = AppointmentType::where('practice_id', $this->practice->id)
            ->where('is_active', true)
            ->get();

        $practitioners = $this->getAvailablePractitioners();

        $selectedType = $this->selectedTypeId
            ? AppointmentType::find($this->selectedTypeId)
            : null;

        $selectedPractitioner = $this->selectedPractitionerId
            ? Practitioner::with('user')->find($this->selectedPractitionerId)
            : null;

        $calendarDays  = $this->step === 3 ? $this->getCalendarDays() : [];
        $availableSlots = $this->step === 3 ? $this->getAvailableSlots() : [];

        $monthLabel = Carbon::create($this->calendarYear, $this->calendarMonth, 1)
            ->format('F Y');

        return view('livewire.public.booking-calendar', compact(
            'appointmentTypes',
            'practitioners',
            'selectedType',
            'selectedPractitioner',
            'calendarDays',
            'availableSlots',
            'monthLabel',
        ));
    }
}
