<?php

namespace App\Filament\Resources\CommunicationRules;

use App\Filament\Resources\CommunicationRules\Pages\CreateCommunicationRule;
use App\Filament\Resources\CommunicationRules\Pages\EditCommunicationRule;
use App\Filament\Resources\CommunicationRules\Pages\ListCommunicationRules;
use App\Filament\Resources\CommunicationRules\Pages\ViewCommunicationRule;
use App\Filament\Resources\CommunicationRules\Schemas\CommunicationRuleForm;
use App\Filament\Resources\CommunicationRules\Tables\CommunicationRulesTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\CommunicationRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunicationRuleResource extends Resource
{
    use BelongsToPractice;

    protected static ?string $model = CommunicationRule::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static string|\UnitEnum|null $navigationGroup = 'Communications';
    protected static ?int $navigationGroupSort = 2;
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CommunicationRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunicationRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCommunicationRules::route('/'),
            'create' => CreateCommunicationRule::route('/create'),
            'view'   => ViewCommunicationRule::route('/{record}'),
            'edit'   => EditCommunicationRule::route('/{record}/edit'),
        ];
    }
}
