<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCheckoutSession extends ViewRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('appointment.patient.name')
                ->label('Patient')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('amount_total')
                ->label('Session Total')
                ->money('USD')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('state')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match($state::class) {
                    'App\Models\States\CheckoutSession\Open' => 'warning',
                    'App\Models\States\CheckoutSession\Paid' => 'success',
                    'App\Models\States\CheckoutSession\Refunded' => 'info',
                    default => 'gray',
                })
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('created_at')
                ->label('Created')
                ->dateTime('M j, Y g:i A')
                ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

            TextEntry::make('charge_label')
                ->label('Charge Label')
                ->placeholder('—'),

            TextEntry::make('notes')
                ->label('Notes')
                ->placeholder('—'),
        ]);
    }
}
