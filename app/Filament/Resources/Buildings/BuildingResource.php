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
use Illuminate\Support\Facades\Gate;

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
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::allows('viewAny', Building::class);
    }

    public static function canCreate(): bool
    {
        if (! static::allows('create', Building::class)) {
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
        return $record instanceof Building
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Building
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Building
            && static::allows('delete', $record);
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

    private static function allows(string $ability, Building|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
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
