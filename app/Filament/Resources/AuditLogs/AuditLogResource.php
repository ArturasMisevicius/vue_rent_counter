<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable(),
                TextColumn::make('metadata_after_status')
                    ->label('After Status')
                    ->state(fn (AuditLog $record): string => (string) data_get($record->metadata, 'after.status', '')),
                TextColumn::make('occurred_at')
                    ->label('Occurred At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc');
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
            ->select([
                'id',
                'organization_id',
                'actor_user_id',
                'action',
                'description',
                'metadata',
                'occurred_at',
            ])
            ->with([
                'actor:id,name,email',
            ]);
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
