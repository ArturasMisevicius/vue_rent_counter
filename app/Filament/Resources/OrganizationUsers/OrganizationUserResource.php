<?php

namespace App\Filament\Resources\OrganizationUsers;

use App\Enums\UserRole;
use App\Filament\Resources\OrganizationUsers\Pages\CreateOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Pages\EditOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Pages\ListOrganizationUsers;
use App\Filament\Resources\OrganizationUsers\Pages\ViewOrganizationUser;
use App\Filament\Resources\OrganizationUsers\Schemas\OrganizationUserForm;
use App\Filament\Resources\OrganizationUsers\Schemas\OrganizationUserInfolist;
use App\Filament\Resources\OrganizationUsers\Tables\OrganizationUsersTable;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\OrganizationUser;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrganizationUserResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = OrganizationUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OrganizationUserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationUserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationUsersTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.relation_resources.organization_users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.relation_resources.organization_users.plural');
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
        return static::allows('viewAny', OrganizationUser::class);
    }

    public static function canCreate(): bool
    {
        return static::allows('create', OrganizationUser::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof OrganizationUser
            && static::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof OrganizationUser
            && static::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof OrganizationUser
            && static::allows('delete', $record);
    }

    /**
     * @return Builder<OrganizationUser>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->select([
                'id',
                'organization_id',
                'user_id',
                'role',
                'permissions',
                'joined_at',
                'left_at',
                'is_active',
                'invited_by',
                'created_at',
                'updated_at',
            ])
            ->with([
                'organization:id,name',
                'user:id,name,email',
                'inviter:id,name',
            ]);

        $user = static::currentUser();

        if ($user?->isSuperadmin()) {
            return $query;
        }

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query
            ->where('organization_id', $organizationId)
            ->where('role', UserRole::MANAGER->value);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, OrganizationUser|string $subject): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $subject);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationUsers::route('/'),
            'create' => CreateOrganizationUser::route('/create'),
            'view' => ViewOrganizationUser::route('/{record}'),
            'edit' => EditOrganizationUser::route('/{record}/edit'),
        ];
    }
}
