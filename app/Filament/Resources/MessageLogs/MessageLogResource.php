<?php

namespace App\Filament\Resources\MessageLogs;

use App\Filament\Resources\MessageLogs\Pages\ListMessageLogs;
use App\Filament\Resources\MessageLogs\Tables\MessageLogsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\MessageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MessageLogResource extends Resource
{
    use BelongsToPractice;

    protected static ?string $model = MessageLog::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
    protected static string|\UnitEnum|null $navigationGroup = 'Communications';
    protected static ?int $navigationGroupSort = 22;
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return MessageLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageLogs::route('/'),
        ];
    }
}
