<?php

namespace App\Filament\Resources\InvoiceReminderLogs;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\InvoiceReminderLogs\Pages\CreateInvoiceReminderLog;
use App\Filament\Resources\InvoiceReminderLogs\Pages\EditInvoiceReminderLog;
use App\Filament\Resources\InvoiceReminderLogs\Pages\ListInvoiceReminderLogs;
use App\Filament\Resources\InvoiceReminderLogs\Pages\ViewInvoiceReminderLog;
use App\Filament\Resources\InvoiceReminderLogs\Schemas\InvoiceReminderLogForm;
use App\Filament\Resources\InvoiceReminderLogs\Schemas\InvoiceReminderLogInfolist;
use App\Filament\Resources\InvoiceReminderLogs\Tables\InvoiceReminderLogsTable;
use App\Models\InvoiceReminderLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceReminderLogResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = InvoiceReminderLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InvoiceReminderLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceReminderLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceReminderLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forSuperadminIndex();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoiceReminderLogs::route('/'),
            'create' => CreateInvoiceReminderLog::route('/create'),
            'view' => ViewInvoiceReminderLog::route('/{record}'),
            'edit' => EditInvoiceReminderLog::route('/{record}/edit'),
        ];
    }
}
