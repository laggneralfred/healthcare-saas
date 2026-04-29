<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSuperbill extends ViewRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected string $view = 'filament.resources.checkout-sessions.pages.view-superbill';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Checkout')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => CheckoutSessionResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
