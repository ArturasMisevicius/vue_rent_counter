<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles;

use App\Filament\Resources\TenantKycProfiles\Pages\ListTenantKycProfiles;
use App\Filament\Resources\TenantKycProfiles\Pages\ViewTenantKycProfile;
use App\Filament\Resources\TenantKycProfiles\RelationManagers\TenantKycDocumentsRelationManager;
use App\Filament\Resources\TenantKycProfiles\Schemas\TenantKycProfileInfolist;
use App\Filament\Resources\TenantKycProfiles\Tables\TenantKycProfilesTable;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\TenantKycProfile;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TenantKycProfileResource extends Resource
{
    protected static ?string $model = TenantKycProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function infolist(Schema $schema): Schema
    {
        return TenantKycProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantKycProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TenantKycDocumentsRelationManager::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('admin.tenant_kyc.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tenant_kyc.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.tenant_kyc.navigation');
    }

    public static function canAccess(): bool
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperadmin() || $user->isAdmin()) {
            return true;
        }

        $organization = $user->currentOrganization();

        return $organization !== null
            && $user->isManager()
            && app(ManagerPermissionService::class)->can($user, $organization, 'tenant_documents', 'edit');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof TenantKycProfile
            && self::allows('view', $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * @return Builder<TenantKycProfile>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'status',
                'submitted_at',
                'reviewed_by_user_id',
                'reviewed_at',
                'approved_at',
                'rejected_at',
                'rejection_reason',
                'expires_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'organization:id,name',
                'tenant:id,organization_id,name,email,role,status',
                'reviewedBy:id,organization_id,name,email,role,status',
            ])
            ->withCount('documents');

        $user = self::currentUser();

        if ($user?->isSuperadmin()) {
            return $query;
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId() ?? $user?->organization_id;

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->forOrganization((int) $organizationId);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTenantKycProfiles::route('/'),
            'view' => ViewTenantKycProfile::route('/{record}'),
        ];
    }

    public static function currentUser(): ?User
    {
        $user = Auth::guard()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @throws AuthenticationException
     */
    public static function currentUserOrFail(): User
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    public static function allows(string $ability, mixed $subject): bool
    {
        $user = self::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
    }
}
