<?php

namespace App\Filament\Resources\Encounters\Widgets;

use App\Models\Encounter;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class EncounterHeader extends Widget
{
    public ?Model $record = null;

    protected string $view = 'filament.resources.encounters.widgets.encounter-header';

    protected function getViewData(): array
    {
        if (!$this->record instanceof Encounter) {
            return [];
        }

        return [
            'patientName'      => $this->record->patient?->name ?? '—',
            'practitionerName' => $this->record->practitioner?->user?->name ?? '—',
            'visitDate'        => $this->record->visit_date?->format('F j, Y') ?? '—',
            'appointmentTime'  => $this->record->appointment?->start_datetime?->format('g:i A') ?? '—',
            'discipline'       => $this->getDisciplineLabel(),
            'status'           => $this->record->status ?? 'draft',
            'statusColor'      => $this->getStatusColor(),
        ];
    }

    private function getDisciplineLabel(): string
    {
        return match ($this->record->discipline) {
            'acupuncture'    => 'Acupuncture',
            'massage'        => 'Massage Therapy',
            'chiropractic'   => 'Chiropractic',
            'physiotherapy'  => 'Physical Therapy',
            default          => ucfirst($this->record->discipline ?? '—'),
        };
    }

    private function getStatusColor(): string
    {
        return match ($this->record->status) {
            'complete' => 'success',
            'draft'    => 'gray',
            default    => 'gray',
        };
    }
}
