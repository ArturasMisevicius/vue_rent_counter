<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Concerns\InteractsWithSubscriptionEnforcement;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Properties\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Properties\RelationManagers\ReadingsRelationManager;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Schemas\PropertyInfolist;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Property;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.properties.navigation');
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        if (! static::allows('create', Property::class)) {
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
        return static::allows('viewAny', Property::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Property
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Property
            && static::allows('update', $record)
            && static::canMutateSubscriptionScopedRecords();
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Property
            && static::allows('delete', $record)
            && static::canMutateSubscriptionScopedRecords();
    }

    /**
     * @return Builder<Property>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return parent::getEloquentQuery()->forSuperadminControlPlane();
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->forOrganizationWorkspace($organizationId);
    }

    public static function getRelations(): array
    {
        return [
            MetersRelationManager::class,
            ReadingsRelationManager::class,
            InvoicesRelationManager::class,
        ];
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

    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, Property|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
    }
}
