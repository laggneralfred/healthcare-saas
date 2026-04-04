<?php
namespace App\Filament\Resources\InventoryMovements\Pages;
use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use Filament\Resources\Pages\ViewRecord;
class ViewInventoryMovement extends ViewRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Movements')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
