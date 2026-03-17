<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Concerns\InteractsWithSubscriptionEnforcement;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Schemas\PropertyInfolist;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Models\Property;
use App\Support\Admin\OrganizationContext;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertyResource extends Resource
{
    use InteractsWithSubscriptionEnforcement;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.properties.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.properties.plural');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user?->isAdmin() && ! $user?->isManager()) {
            return false;
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return false;
        }

        return ! static::getSubscriptionAccessState()->blocksCreation('properties');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Property
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record)
            && static::canMutateSubscriptionScopedRecords();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    /**
     * @return Builder<Property>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forOrganizationWorkspace($organizationId);
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
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }
}
