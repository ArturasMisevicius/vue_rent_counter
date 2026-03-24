<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Schemas\AuditLogTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    public static function table(Table $table): Table
    {
        return AuditLogTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return 'Audit Log';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Audit Logs';
    }

    /**
     * @return Builder<AuditLog>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forAuditFeed();
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
