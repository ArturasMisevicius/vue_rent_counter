<?php

namespace App\Filament\Resources\SecurityViolations;

use App\Filament\Resources\SecurityViolations\Pages\ListSecurityViolations;
use App\Models\SecurityViolation;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityViolationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SecurityViolation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('summary')
                    ->label('Summary')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst((string) ($state->value ?? $state))),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getModelLabel(): string
    {
        return 'Security Violation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Security Violations';
    }

    /**
     * @return Builder<SecurityViolation>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'user_id',
                'type',
                'severity',
                'ip_address',
                'summary',
                'occurred_at',
            ])
            ->with([
                'organization:id,name',
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
            'index' => ListSecurityViolations::route('/'),
        ];
    }
}
