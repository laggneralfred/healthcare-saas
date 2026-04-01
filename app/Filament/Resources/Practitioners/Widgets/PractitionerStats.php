<?php

namespace App\Filament\Resources\Practitioners\Widgets;

use App\Models\Practitioner;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Completed;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class PractitionerStats extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $total = $this->record->appointments()->count();
        
        if ($total === 0) {
            return [
                Stat::make('Completion Rate', '0%'),
                Stat::make('Cancellation Rate', '0%'),
            ];
        }

        $completed = $this->record->appointments()->where('status', Completed::$name)->count();
        $cancelled = $this->record->appointments()->where('status', Cancelled::$name)->count();

        $completionRate = round(($completed / $total) * 100);
        $cancellationRate = round(($cancelled / $total) * 100);

        return [
            Stat::make('Completion Rate', $completionRate . '%')
                ->description('Appointments marked as completed')
                ->color('success'),
            Stat::make('Cancellation Rate', $cancellationRate . '%')
                ->description('Appointments marked as cancelled')
                ->color('danger'),
        ];
    }
}
