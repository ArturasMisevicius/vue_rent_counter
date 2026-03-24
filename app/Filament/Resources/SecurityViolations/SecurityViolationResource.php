<?php

namespace App\Filament\Resources\SecurityViolations;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\SecurityViolations\Pages\ListSecurityViolations;
use App\Filament\Resources\SecurityViolations\Schemas\SecurityViolationTable;
use App\Models\SecurityViolation;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityViolationResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SecurityViolation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    public static function table(Table $table): Table
    {
        return SecurityViolationTable::configure($table);
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
        return parent::getEloquentQuery()->forSuperadminFeed();
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
