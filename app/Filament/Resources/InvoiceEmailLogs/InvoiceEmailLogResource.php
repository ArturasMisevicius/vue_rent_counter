<?php

namespace App\Filament\Resources\InvoiceEmailLogs;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\InvoiceEmailLogs\Pages\CreateInvoiceEmailLog;
use App\Filament\Resources\InvoiceEmailLogs\Pages\EditInvoiceEmailLog;
use App\Filament\Resources\InvoiceEmailLogs\Pages\ListInvoiceEmailLogs;
use App\Filament\Resources\InvoiceEmailLogs\Pages\ViewInvoiceEmailLog;
use App\Filament\Resources\InvoiceEmailLogs\Schemas\InvoiceEmailLogForm;
use App\Filament\Resources\InvoiceEmailLogs\Schemas\InvoiceEmailLogInfolist;
use App\Filament\Resources\InvoiceEmailLogs\Tables\InvoiceEmailLogsTable;
use App\Models\InvoiceEmailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceEmailLogResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = InvoiceEmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InvoiceEmailLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceEmailLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceEmailLogsTable::configure($table);
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
            'index' => ListInvoiceEmailLogs::route('/'),
            'create' => CreateInvoiceEmailLog::route('/create'),
            'view' => ViewInvoiceEmailLog::route('/{record}'),
            'edit' => EditInvoiceEmailLog::route('/{record}/edit'),
        ];
    }
}
