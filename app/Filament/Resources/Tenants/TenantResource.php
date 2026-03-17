<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Concerns\InteractsWithSubscriptionEnforcement;
use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Schemas\TenantInfolist;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Models\User;
use App\Support\Admin\OrganizationContext;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return parent::getEloquentQuery()->whereKey(-1);
        }

        return parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId)
            ->where('role', 'tenant')
            ->with([
                'currentPropertyAssignment:id,organization_id,property_id,tenant_user_id,unit_area_sqm,assigned_at,unassigned_at',
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,city',
                'invoices:id,organization_id,property_id,tenant_user_id,invoice_number,status,currency,total_amount,amount_paid,due_date',
            ]);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
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

        return ! static::getSubscriptionAccessState()->blocksCreation('tenants');
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof User
            && $record->isTenant()
            && $record->organization_id === app(OrganizationContext::class)->currentOrganizationId()
            && ($user?->isAdmin() || $user?->isManager());
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
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
