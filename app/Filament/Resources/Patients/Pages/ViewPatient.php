<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Models\States\Appointment\Cancelled as AppointmentCancelled;
use App\Models\States\CheckoutSession\Paid;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('edit')
                ->label('Edit Patient')
                ->icon('heroicon-o-pencil')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record]))
                ->color('gray'),

            Action::make('new_encounter')
                ->label('New Visit')
                ->icon('heroicon-o-document')
                ->url(fn () => \App\Filament\Resources\Encounters\EncounterResource::getUrl('create', ['patient_id' => $this->record->id]))
                ->color('primary'),

            Action::make('new_appointment')
                ->label('New Appointment')
                ->icon('heroicon-o-calendar')
                ->url(fn () => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create', ['patient_id' => $this->record->id]))
                ->color('success'),
        ];
    }

    protected function resolveRecord($key): Model
    {
        return Patient::with([
            'encounters'        => fn ($q) => $q->with('practitioner.user')->orderByDesc('visit_date'),
            'appointments'      => fn ($q) => $q->with('practitioner.user', 'appointmentType', 'encounter')->orderByDesc('start_datetime'),
            'medicalHistories' => fn ($q) => $q->where('status', 'complete')->latest(),
            'checkoutSessions'  => fn ($q) => $q->latest(),
            'consentRecords'    => fn ($q) => $q->where('status', 'complete')->latest(),
        ])->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        $patient       = $this->record;
        $latestIntake  = $patient->medicalHistories->first();
        $encounters    = $patient->encounters;
        $lastEncounter = $encounters->first();

        $nextAppointment = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isFuture()
                && !($a->status instanceof AppointmentCancelled))
            ->sortBy('start_datetime')
            ->first();

        $pastAppointments = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isPast())
            ->sortByDesc('start_datetime')
            ->take(10);

        $upcomingAppointments = $patient->appointments
            ->filter(fn ($a) => $a->start_datetime->isFuture()
                && !($a->status instanceof AppointmentCancelled))
            ->sortBy('start_datetime');

        $outstandingBalance = $patient->checkoutSessions
            ->filter(fn ($c) => !($c->state instanceof Paid))
            ->sum('amount_total');

        $hasCompletedIntake   = $latestIntake !== null;
        $hasSignedConsent     = $patient->consentRecords->isNotEmpty();
        $hasOutstandingPayment = $outstandingBalance > 0;

        // Determine status: New (no visits), Active (visited in 12 months), Inactive (older)
        if (! $lastEncounter) {
            $status = 'new';
        } elseif (now()->diffInMonths($lastEncounter->visit_date, false) >= -12) {
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        return $schema->components([
            ViewEntry::make('overview')
                ->view('filament.resources.patients.view-patient')
                ->viewData([
                    'patient'               => $patient,
                    'latestIntake'          => $latestIntake,
                    'encounters'            => $encounters,
                    'lastEncounter'         => $lastEncounter,
                    'nextAppointment'       => $nextAppointment,
                    'upcomingAppointments'  => $upcomingAppointments,
                    'pastAppointments'      => $pastAppointments,
                    'outstandingBalance'    => $outstandingBalance,
                    'hasCompletedIntake'    => $hasCompletedIntake,
                    'hasSignedConsent'      => $hasSignedConsent,
                    'hasOutstandingPayment' => $hasOutstandingPayment,
                    'status'                => $status,
                    'checkoutSessions'      => $patient->checkoutSessions,
                ])
                ->columnSpanFull(),
        ]);
    }
}
