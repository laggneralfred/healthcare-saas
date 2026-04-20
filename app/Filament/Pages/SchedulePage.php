<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AppointmentCalendarWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class SchedulePage extends Page
{
    protected static ?string               $slug            = 'schedule';
    protected static ?string               $title           = 'Schedule';
    protected static ?string               $navigationLabel = 'Calendar View';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|\UnitEnum|null  $navigationGroup = 'Schedule';
    protected static ?int                  $navigationSort  = 1;
    protected string $view = 'filament.pages.schedule';

    public function getTitle(): string
    {
        return 'Schedule';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentCalendarWidget::class,
        ];
    }
}
