<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Concerns\InteractsWithSubscriptionEnforcement;
use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\RelationManagers\AuditTrailRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\ReadingsRelationManager;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Schemas\TenantInfolist;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
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

class TenantResource extends Resource
{
    use InteractsWithSubscriptionEnforcement;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TenantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.tenants.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tenants.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.tenants.navigation');
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

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return parent::getEloquentQuery()->forTenantControlPlane();
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()->withTenantWorkspaceSummary($organizationId);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getModel()::query()->tenants();
    }

    public static function canViewAny(): bool
    {
        $user = self::currentUser();

        return $user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager();
    }

    public static function canCreate(): bool
    {
        $user = self::currentUser();

        if (! $user?->isAdmin() && ! $user?->isManager()) {
            return false;
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return false;
        }

        if ($user->isManager()) {
            $organization = $user->currentOrganization();

            if ($organization === null || ! app(ManagerPermissionService::class)->can($user, $organization, 'tenants', 'create')) {
                return false;
            }
        }

        return ! static::getSubscriptionAccessState()->blocksCreation('tenants');
    }

    public static function canView(Model $record): bool
    {
        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return $record instanceof User && $record->isTenant();
        }

        return $record instanceof User
            && $record->isTenant()
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && ($user?->isAdmin() || $user?->isManager());
    }

    public static function canEdit(Model $record): bool
    {
        $user = self::currentUser();

        if ($user?->isManager()) {
            $organization = $user->currentOrganization();

            if ($organization === null || ! app(ManagerPermissionService::class)->can($user, $organization, 'tenants', 'edit')) {
                return false;
            }
        }

        return static::canView($record)
            && static::canMutateSubscriptionScopedRecords();
    }

    public static function canDelete(Model $record): bool
    {
        $user = self::currentUser();

        if ($user?->isManager()) {
            $organization = $user->currentOrganization();

            if ($organization === null || ! app(ManagerPermissionService::class)->can($user, $organization, 'tenants', 'delete')) {
                return false;
            }
        }

        return static::canView($record)
            && static::canMutateSubscriptionScopedRecords();
    }

    public static function getRelations(): array
    {
        return [
            MetersRelationManager::class,
            ReadingsRelationManager::class,
            InvoicesRelationManager::class,
            AuditTrailRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }
}
