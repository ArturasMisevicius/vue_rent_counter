<?php

namespace App\Filament\Resources\Buildings;

use App\Filament\Resources\Buildings\Pages\CreateBuilding;
use App\Filament\Resources\Buildings\Pages\EditBuilding;
use App\Filament\Resources\Buildings\Pages\ListBuildings;
use App\Filament\Resources\Buildings\Pages\ViewBuilding;
use App\Filament\Resources\Buildings\RelationManagers\PropertiesRelationManager;
use App\Filament\Resources\Buildings\Schemas\BuildingForm;
use App\Filament\Resources\Buildings\Schemas\BuildingInfolist;
use App\Filament\Resources\Buildings\Tables\BuildingsTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
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

class BuildingResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Building::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BuildingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BuildingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BuildingsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.buildings.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.buildings.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.buildings.navigation');
    }

    public static function canAccess(): bool
    {
        $user = self::currentUser();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        $user = self::currentUser();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    public static function canCreate(): bool
    {
        if (! static::canViewAny()) {
            return false;
        }

        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return true;
        }

        return app(OrganizationContext::class)->currentOrganizationId() !== null;
    }

    public static function canView(Model $record): bool
    {
        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return true;
        }

        return $record instanceof Building
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canView($record);
    }

    /**
     * @return Builder<Building>
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getModel()::query();
    }

    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function getRelations(): array
    {
        return [
            PropertiesRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListBuildings::route('/'),
            'create' => CreateBuilding::route('/create'),
            'view' => ViewBuilding::route('/{record}'),
            'edit' => EditBuilding::route('/{record}/edit'),
        ];
    }
}
