<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\ManagersRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\PropertiesRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.organizations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.organizations.plural');
    }

    /**
     * @return Builder<Organization>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forSuperadminControlPlane();
    }

    public static function getRelations(): array
    {
        return [
            'users' => UsersRelationManager::class,
            'managers' => ManagersRelationManager::class,
            'subscriptions' => SubscriptionsRelationManager::class,
            'properties' => PropertiesRelationManager::class,
            'activity-logs' => ActivityLogsRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'view' => ViewOrganization::route('/{record}'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
