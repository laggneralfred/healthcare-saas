<?php
namespace App\Filament\Resources\InventoryProducts\Pages;
use App\Filament\Resources\InventoryProducts\InventoryProductResource;
use Filament\Resources\Pages\ViewRecord;
class ViewInventoryProduct extends ViewRecord
{
    protected static string $resource = InventoryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Products')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
